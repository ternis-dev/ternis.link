<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Link;
use App\Services\BotProtectionService;
use App\Services\ClickTrackerService;
use App\Services\LinkService;
use App\Services\TargetSelector;
use App\Services\TurnstileService;
use App\Support\RedirectResilience;
use App\Support\UserTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ProtectedGatewayController extends Controller
{
    public function __construct(
        private BotProtectionService $botProtection,
        private TurnstileService $turnstile,
        private ClickTrackerService $clickTracker,
        private TargetSelector $targets,
    ) {}

    /**
     * Landing page for protected.ternis.link.
     */
    public function index()
    {
        return view('protected.landing');
    }

    /**
     * Standard bot-protection challenge gate.
     */
    public function challenge(Request $request)
    {
        $linkId = (string) $request->query('link', '');
        $rid = (string) $request->query('rid', '');
        $ts = (int) $request->query('ts', 0);
        $sig = (string) $request->query('sig', '');

        if (! $this->botProtection->verifyChallenge($linkId, $rid, $ts, $sig)) {
            abort(403, 'Invalid or expired security challenge signature.');
        }

        $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);

        // If visitor is already verified human and link is not password protected, redirect immediately.
        if ($this->botProtection->isHumanVerified($request) && $link->password_hash === null) {
            return $this->resolveAndRedirect($link, $request);
        }

        return response()->view('protected.challenge', [
            'link' => $link,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'turnstileKey' => config('services.turnstile.key'),
            'requiresPassword' => $link->password_hash !== null,
        ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
    }

    /**
     * Specialized challenge gate for suspicious user-agent signatures.
     */
    public function userAgent(Request $request)
    {
        $linkId = (string) $request->query('link', '');
        $rid = (string) $request->query('rid', '');
        $ts = (int) $request->query('ts', 0);
        $sig = (string) $request->query('sig', '');

        if (! $this->botProtection->verifyChallenge($linkId, $rid, $ts, $sig)) {
            abort(403, 'Invalid or expired security challenge signature.');
        }

        $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);

        return response()->view('protected.user-agent', [
            'link' => $link,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'detectedUa' => $request->userAgent() ?: 'Unknown / Empty Client String',
            'turnstileKey' => config('services.turnstile.key'),
            'requiresPassword' => $link->password_hash !== null,
        ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
    }

    /**
     * Verify Turnstile challenge token and optional link password.
     */
    public function verify(Request $request)
    {
        // Rate-limit challenge verifications per IP.
        $rateLimitKey = 'bot_verify:'.($request->ip() ?? 'unknown');
        $maxAttempts = (int) config('bot_protection.rate_limit.max_attempts', 30);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->view('errors.429', [
                'message' => "Too many verification attempts. Please retry in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $linkId = (string) $request->input('link', '');
        $rid = (string) $request->input('rid', '');
        $ts = (int) $request->input('ts', 0);
        $sig = (string) $request->input('sig', '');

        if (! $this->botProtection->verifyChallenge($linkId, $rid, $ts, $sig)) {
            return back()->withErrors(['challenge' => 'Security challenge signature expired or invalid. Please re-open the link.']);
        }

        $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);

        // Verify Turnstile response token if Turnstile is enabled.
        if ($this->turnstile->isEnabled()) {
            $turnstileToken = (string) $request->input('cf-turnstile-response', '');
            $verified = $this->turnstile->verify(
                $turnstileToken,
                $request->ip(),
                expectedAction: 'verify_human',
                expectedHostname: $request->getHost(),
            );

            if (! $verified) {
                return back()->withErrors(['turnstile' => 'Turnstile verification failed. Please complete the challenge again.']);
            }
        }

        // Verify password if the link is password-protected.
        if ($link->password_hash !== null) {
            $password = (string) $request->input('password', '');
            if (! Hash::check($password, $link->password_hash)) {
                return back()->withErrors(['password' => 'Wrong password — please try again.']);
            }

            $request->session()->put(LinkService::sessionKey($link->id), true);
        }

        // Mark human verification in session.
        $request->session()->put('tl_human_verified_at', time());

        $cookie = $this->botProtection->makeHumanVerificationCookie($request->getHost());

        return $this->resolveAndRedirect($link, $request)->withCookie($cookie);
    }

    /**
     * Password unlock on protected gateway.
     */
    public function unlock(Request $request)
    {
        $rateLimitKey = 'bot_unlock:'.($request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            return response()->view('errors.429', ['message' => 'Too many unlock attempts.'], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $linkId = (string) $request->input('link', '');
        $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);

        if ($link->password_hash === null) {
            return $this->resolveAndRedirect($link, $request);
        }

        $password = (string) $request->input('password', '');
        if (! Hash::check($password, $link->password_hash)) {
            return back()->withErrors(['password' => 'Wrong password — please try again.']);
        }

        $request->session()->put(LinkService::sessionKey($link->id), true);

        return $this->resolveAndRedirect($link, $request);
    }

    /**
     * Resolve target, apply UTM parameters and tracking, and redirect to final destination.
     */
    private function resolveAndRedirect(Link $link, Request $request)
    {
        $target = RedirectResilience::optional(fn () => $this->targets->pick($link, $request));
        $destination = LinkService::destinationWithUtm($link, $target?->destination_url ?? (string) $link->destination_url);

        $incomingQuery = $request->query();
        if (is_array($incomingQuery) && $incomingQuery !== []) {
            // Remove challenge parameters from destination query pass-through
            unset($incomingQuery['link'], $incomingQuery['rid'], $incomingQuery['ts'], $incomingQuery['sig']);
            if ($incomingQuery !== []) {
                $destination = UserTracking::mergeQueryIntoDestination($destination, $incomingQuery);
            }
        }

        RedirectResilience::optional(fn () => $this->clickTracker->track($link, $request, target: $target));

        return redirect()->away($destination, 302);
    }
}
