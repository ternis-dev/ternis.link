<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\ThreatEvaluation;
use App\Services\AiThreatDetectionService;
use App\Services\BotProtectionService;
use App\Services\ClickTrackerService;
use App\Services\CustomCaptchaService;
use App\Services\LinkService;
use App\Services\TargetSelector;
use App\Services\TurnstileService;
use App\Support\DebugLinkReference;
use App\Support\DomainUrls;
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
        private AiThreatDetectionService $aiThreat,
        private CustomCaptchaService $customCaptcha,
    ) {}

    /**
     * Check if the authenticated user has administrative privileges.
     */
    private function isAdmin(): bool
    {
        return auth()->check() && (bool) auth()->user()?->isAdmin();
    }

    /**
     * Landing page for protected.ternis.link.
     */
    public function index(Request $request)
    {
        $isAdmin = $this->isAdmin();

        $stats = [
            'turnstile_configured' => ! empty(config('services.turnstile.key')),
            'ai_security_enabled' => (bool) config('ai_security.enabled', true),
            'ai_model' => (string) config('ai_security.model', 'jev-latest'),
            'rate_limit_max' => (int) config('bot_protection.rate_limit.max_attempts', 30),
            'remember_minutes' => (int) config('bot_protection.remember_duration_minutes', 120),
            'is_human_verified' => $this->botProtection->isHumanVerified($request),
            'visitor_ip' => $request->ip() ?? 'Unknown',
            'visitor_ua' => $request->userAgent() ?: 'Unknown Browser',
            'total_evaluations' => ThreatEvaluation::count(),
            'quarantined_threats' => ThreatEvaluation::where('action_taken', 'quarantined')->count(),
        ];

        return view('protected.landing', [
            'isAdmin' => $isAdmin,
            'stats' => $stats,
        ]);
    }

    /**
     * Standard bot-protection challenge gate.
     */
    public function challenge(Request $request)
    {
        $linkId = (string) $request->query('link', '');
        $dest = (string) $request->query('dest', '');
        $rid = (string) $request->query('rid', '');
        $ts = (int) $request->query('ts', 0);
        $sig = (string) $request->query('sig', '');

        $targetKey = $linkId !== '' ? $linkId : 'dest:'.$dest;

        if (! $this->botProtection->verifyChallenge($targetKey, $rid, $ts, $sig)) {
            abort(403, 'Invalid or expired security challenge signature.');
        }

        $link = null;
        if ($linkId !== '') {
            $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);

            // If visitor is already verified human and link is not password protected, redirect immediately.
            if ($this->botProtection->isHumanVerified($request) && $link->password_hash === null) {
                return $this->resolveAndRedirect($link, $request);
            }
        } elseif ($dest !== '' && $this->botProtection->isHumanVerified($request)) {
            $targetUrl = str_starts_with($dest, 'http') ? $dest : url($dest);

            return redirect()->away($targetUrl);
        }

        $protectionLevel = $link ? $link->getBotProtectionLevel() : 'standard';
        $useCustomCaptcha = $protectionLevel === 'custom_captcha'
            || empty(config('services.turnstile.key'))
            || $request->query('mode') === 'captcha';

        $captcha = $this->customCaptcha->generate();

        return response()->view('protected.challenge', [
            'link' => $link,
            'dest' => $dest,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'turnstileKey' => config('services.turnstile.key'),
            'requiresPassword' => $link && $link->password_hash !== null,
            'useCustomCaptcha' => $useCustomCaptcha,
            'captcha' => $captcha,
            'protectionLevel' => $protectionLevel,
        ], 200, ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex']);
    }

    /**
     * Specialized challenge gate for suspicious user-agent signatures.
     */
    public function userAgent(Request $request)
    {
        $linkId = (string) $request->query('link', '');
        $dest = (string) $request->query('dest', '');
        $rid = (string) $request->query('rid', '');
        $ts = (int) $request->query('ts', 0);
        $sig = (string) $request->query('sig', '');

        $targetKey = $linkId !== '' ? $linkId : 'dest:'.$dest;

        if (! $this->botProtection->verifyChallenge($targetKey, $rid, $ts, $sig)) {
            abort(403, 'Invalid or expired security challenge signature.');
        }

        $link = null;
        if ($linkId !== '') {
            $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);
        }

        $protectionLevel = $link ? $link->getBotProtectionLevel() : 'standard';
        $useCustomCaptcha = $protectionLevel === 'custom_captcha'
            || empty(config('services.turnstile.key'))
            || $request->query('mode') === 'captcha';

        $captcha = $this->customCaptcha->generate();

        return response()->view('protected.user-agent', [
            'link' => $link,
            'dest' => $dest,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $sig,
            'detectedUa' => $request->userAgent() ?: 'Unknown / Empty Client String',
            'turnstileKey' => config('services.turnstile.key'),
            'requiresPassword' => $link && $link->password_hash !== null,
            'useCustomCaptcha' => $useCustomCaptcha,
            'captcha' => $captcha,
            'protectionLevel' => $protectionLevel,
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
        $dest = (string) $request->input('dest', '');
        $rid = (string) $request->input('rid', '');
        $ts = (int) $request->input('ts', 0);
        $sig = (string) $request->input('sig', '');

        $targetKey = $linkId !== '' ? $linkId : 'dest:'.$dest;

        if (! $this->botProtection->verifyChallenge($targetKey, $rid, $ts, $sig)) {
            return back()->withErrors(['challenge' => 'Security challenge signature expired or invalid. Please re-open the link.'])->withInput();
        }

        $link = null;
        if ($linkId !== '') {
            $link = Link::with('domain')->where('is_removed', false)->findOrFail($linkId);
        } elseif ($dest !== '') {
            if (! str_starts_with($dest, '/') || str_starts_with($dest, '//')) {
                abort(400, 'Invalid destination path.');
            }
        } else {
            abort(400, 'Missing link or destination parameter.');
        }

        // Custom CAPTCHA verification if token or answer was submitted
        $captchaToken = (string) $request->input('custom_captcha_token', '');
        $captchaAnswer = (string) $request->input('custom_captcha_answer', '');

        if ($captchaToken !== '' || $captchaAnswer !== '') {
            if (! $this->customCaptcha->verify($captchaToken, $captchaAnswer)) {
                return back()->withErrors(['captcha' => 'The arithmetic verification answer was incorrect or expired. Please try again.'])->withInput();
            }
        } elseif ($this->turnstile->isEnabled()) {
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
        if ($link && $link->password_hash !== null) {
            $password = (string) $request->input('password', '');
            if (! Hash::check($password, $link->password_hash)) {
                return back()->withErrors(['password' => 'Wrong password — please try again.']);
            }

            $request->session()->put(LinkService::sessionKey($link->id), true);
        }

        // Mark human verification in session.
        $request->session()->put('tl_human_verified_at', time());

        $cookie = $this->botProtection->makeHumanVerificationCookie($request->getHost());

        if ($link) {
            return $this->resolveAndRedirect($link, $request)->withCookie($cookie);
        }

        $targetUrl = str_starts_with($dest, 'http') ? $dest : url($dest);

        return redirect()->away($targetUrl)->withCookie($cookie);
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
     * Admin-only real-time diagnostic console and AI request inspector.
     */
    public function debug(Request $request)
    {
        if (! $this->isAdmin()) {
            abort(403, 'Admin access required. Please sign in to an administrator account on ternis.link or dash.ternis.link.');
        }

        $ip = $request->ip() ?? 'unknown';
        $rateLimitKey = 'bot_verify:'.$ip;
        $maxAttempts = (int) config('bot_protection.rate_limit.max_attempts', 30);
        $attempts = RateLimiter::attempts($rateLimitKey);
        $remaining = max(0, $maxAttempts - $attempts);

        // Analyze current verification cookie if present
        $cookiePayload = $this->botProtection->inspectHumanCookie($request);

        // Optional test link
        $testSlug = (string) ($request->query('slug') ?: $request->query('link') ?: '');
        $testLink = DebugLinkReference::resolve($testSlug);

        $decisionExplanation = $this->botProtection->explainProtectionDecision($testLink, $request);

        // Run live AI request triage on the current admin request
        $liveAiAnalysis = $this->runAiAnalysis(
            $request->userAgent() ?: '',
            $request->path(),
            $request->headers->all()
        );

        $diagnostics = [
            'client' => [
                'ip' => $ip,
                'remote_addr' => $request->server('REMOTE_ADDR'),
                'cf_connecting_ip' => $request->header('cf-connecting-ip'),
                'x_forwarded_for' => $request->header('x-forwarded-for'),
                'user_agent' => $request->userAgent() ?: 'Unknown',
                'method' => $request->method(),
                'scheme' => $request->getScheme(),
                'is_secure' => $request->isSecure(),
                'protocol' => $request->server('SERVER_PROTOCOL'),
                'host' => $request->getHost(),
            ],
            'verification' => [
                'is_human_verified' => $this->botProtection->isHumanVerified($request),
                'session_verified_at' => $request->session()->get('tl_human_verified_at'),
                'cookie_details' => $cookiePayload,
            ],
            'rate_limiting' => [
                'key' => $rateLimitKey,
                'attempts_used' => $attempts,
                'max_attempts' => $maxAttempts,
                'remaining' => $remaining,
                'available_in_seconds' => RateLimiter::availableIn($rateLimitKey),
            ],
            'configuration' => [
                'bot_protection_enabled' => $this->botProtection->isEnabled(),
                'filter_suspicious_ua' => $this->botProtection->shouldFilterUserAgents(),
                'turnstile_site_key_set' => ! empty(config('services.turnstile.key')),
                'turnstile_secret_set' => ! empty(config('services.turnstile.secret')),
                'ai_security_enabled' => $this->aiThreat->isEnabled(),
                'ai_api_key_set' => ! empty(config('ai_security.api_key')),
                'ai_model' => config('ai_security.model', 'jev-latest'),
                'remember_duration_minutes' => config('bot_protection.remember_duration_minutes', 120),
                'cookie_name' => config('bot_protection.cookie_name', 'tl_human_verified'),
                'suspicious_ua_patterns_count' => count((array) config('bot_protection.suspicious_user_agents', [])),
            ],
            'headers' => $request->headers->all(),
            'ai_analysis' => $liveAiAnalysis,
            'decision_explanation' => $decisionExplanation,
            'test_link' => $testLink,
        ];

        return view('protected.debug', [
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * Admin toggle to simulate / set or clear the human verification cookie.
     */
    public function debugToggleCookie(Request $request)
    {
        if (! $this->isAdmin()) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        $action = (string) $request->input('action', 'issue');

        if ($action === 'clear') {
            $cookie = $this->botProtection->makeExpiredHumanVerificationCookie($request->getHost());

            return back()->withCookie($cookie)->with('success', 'Human verification cookie cleared.');
        }

        $cookie = $this->botProtection->makeHumanVerificationCookie($request->getHost());

        return back()->withCookie($cookie)->with('success', 'Issued fresh human verification cookie (120 minutes validity).');
    }

    /**
     * Admin interactive simulation of the protection decision engine.
     */
    public function debugSimulate(Request $request)
    {
        if (! $this->isAdmin()) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        $slugOrId = trim((string) $request->input('link_slug_or_id', ''));
        $userAgent = (string) $request->input('user_agent', $request->userAgent() ?: 'curl/8.4.0');
        $hasCookieInput = $request->input('has_cookie');
        $overrideCookie = $hasCookieInput === 'yes' ? true : ($hasCookieInput === 'no' ? false : null);

        $link = DebugLinkReference::resolve($slugOrId);

        $result = $this->botProtection->explainProtectionDecision($link, $request, $userAgent, $overrideCookie);

        return response()->json([
            'link' => $link ? ['id' => $link->id, 'slug' => $link->slug, 'short_url' => $link->short_url, 'bot_protection' => $link->hasBotProtection(), 'allows_bots' => $link->allowsBots()] : null,
            'user_agent' => $userAgent,
            'result' => $result,
        ]);
    }

    /**
     * Admin on-demand AI request analysis endpoint.
     */
    public function analyzeRequest(Request $request)
    {
        if (! $this->isAdmin()) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        $userAgent = (string) ($request->input('user_agent') ?? $request->userAgent() ?? '');
        $path = (string) ($request->input('path') ?? '/');
        $customHeaders = (array) ($request->input('headers') ?? []);

        $result = $this->runAiAnalysis($userAgent, $path, $customHeaders ?: $request->headers->all());

        return response()->json($result);
    }

    /**
     * Public interactive client signature classifier.
     */
    public function classifyClient(Request $request)
    {
        $ip = $request->ip() ?? 'unknown';
        $rateLimitKey = 'bot_classify:'.$ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 30)) {
            return response()->json([
                'error' => 'Rate limit exceeded. Please wait a minute before analyzing more signatures.',
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        $userAgent = (string) ($request->input('user_agent') ?? $request->userAgent() ?? '');
        $path = (string) ($request->input('path') ?? '/');

        $result = $this->runAiAnalysis($userAgent, $path);

        return response()->json($result);
    }

    /**
     * Public verification / Request ID lookup utility.
     */
    public function lookupRequestId(Request $request)
    {
        $rid = trim((string) $request->input('rid', ''));

        if ($rid === '') {
            return response()->json(['error' => 'Please provide a Request ID (rid).'], 422);
        }

        if (! preg_match('/^rid_[0-9a-z]+$/i', $rid)) {
            return response()->json([
                'valid_format' => false,
                'message' => 'Invalid Request ID format. Valid IDs begin with "rid_" followed by a cryptographic ULID string.',
            ]);
        }

        return response()->json([
            'valid_format' => true,
            'request_id' => $rid,
            'message' => 'Valid Request ID signature format.',
            'generated_type' => 'Cryptographic ULID (time-ordered & collision-resistant)',
            'time_window_seconds' => 900,
        ]);
    }

    /**
     * Admin challenge signature testing and generation utility.
     */
    public function testSignature(Request $request)
    {
        if (! $this->isAdmin()) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        $linkId = (string) $request->input('link_id', 'test_link_123');
        $rid = (string) $request->input('rid', $this->botProtection->generateRequestId());
        $ts = (int) ($request->input('ts') ?: time());

        $signature = $this->botProtection->signChallenge($linkId, $rid, $ts);
        $isValid = $this->botProtection->verifyChallenge($linkId, $rid, $ts, $signature);

        $challengeUrl = DomainUrls::protected('/challenge?'.http_build_query([
            'link' => $linkId,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $signature,
        ]));

        $uaUrl = DomainUrls::protected('/user-agent?'.http_build_query([
            'link' => $linkId,
            'rid' => $rid,
            'ts' => $ts,
            'sig' => $signature,
        ]));

        return response()->json([
            'link_id' => $linkId,
            'request_id' => $rid,
            'timestamp' => $ts,
            'signature' => $signature,
            'is_valid' => $isValid,
            'challenge_url' => $challengeUrl,
            'user_agent_url' => $uaUrl,
        ]);
    }

    /**
     * Admin utility to clear verification rate-limit attempts.
     */
    public function clearRateLimit(Request $request)
    {
        if (! $this->isAdmin()) {
            return response()->json(['error' => 'Admin access required.'], 403);
        }

        $targetIp = (string) ($request->input('ip') ?: $request->ip() ?: 'unknown');
        RateLimiter::clear('bot_verify:'.$targetIp);
        RateLimiter::clear('bot_unlock:'.$targetIp);
        RateLimiter::clear('bot_classify:'.$targetIp);

        return response()->json([
            'success' => true,
            'message' => "Cleared rate limit buckets for IP: {$targetIp}",
        ]);
    }

    /**
     * Run AI analysis and heuristic checks on a request signature.
     */
    private function runAiAnalysis(string $userAgent, string $path = '/', array $headers = []): array
    {
        $suspiciousPatternMatch = $this->botProtection->isSuspiciousUserAgent($userAgent);

        // Try AI model client if available
        $aiResult = $this->aiThreat->evaluateUserAgent($userAgent, $path);

        // Fallback or heuristic enhancement if AI client is unconfigured or in unverified mode
        if ($aiResult['verdict'] === 'unverified' || empty($aiResult['probabilities'])) {
            $heuristic = $this->classifyUserAgentHeuristically($userAgent);
            $aiResult['verdict'] = $heuristic['verdict'];
            $aiResult['score'] = $heuristic['score'];
            $aiResult['confidence'] = $heuristic['confidence'];
            $aiResult['probabilities'] = $heuristic['probabilities'];
            $aiResult['reasons'] = $heuristic['reasons'];
            $aiResult['mode'] = 'heuristic';
        } else {
            $aiResult['mode'] = 'ai_live';
            $aiResult['reasons'] = [
                'Live probabilistic AI evaluation complete',
                'SystemOne model classification verified',
            ];
        }

        $score = (int) ($aiResult['score'] ?? 0);
        $action = match (true) {
            $score >= 80 || $aiResult['verdict'] === 'exploit_scanner' => 'CHALLENGE_UA',
            $suspiciousPatternMatch || $score >= 50 || in_array($aiResult['verdict'], ['aggressive_scraper', 'benign_tool'], true) => 'CHALLENGE_TURNSTILE',
            default => 'ALLOW',
        };

        return [
            'user_agent' => $userAgent,
            'path' => $path,
            'verdict' => $aiResult['verdict'],
            'score' => $score,
            'confidence' => (float) ($aiResult['confidence'] ?? 0.8),
            'probabilities' => $aiResult['probabilities'] ?? [],
            'is_suspicious_ua' => $suspiciousPatternMatch,
            'recommended_action' => $action,
            'mode' => $aiResult['mode'] ?? 'ai_live',
            'reasons' => $aiResult['reasons'] ?? [],
            'analyzed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Heuristic classifier when AI client is unconfigured or offline.
     */
    private function classifyUserAgentHeuristically(string $userAgent): array
    {
        $ua = strtolower(trim($userAgent));

        if ($ua === '') {
            return [
                'verdict' => 'aggressive_scraper',
                'score' => 90,
                'confidence' => 0.95,
                'probabilities' => ['aggressive_scraper' => 0.85, 'exploit_scanner' => 0.15],
                'reasons' => ['Blank or missing User-Agent header (classic scraper anomaly)'],
            ];
        }

        if (preg_match('/(sqlmap|nikto|masscan|nmap|acunetix|nessus|dirbuster|gobuster)/i', $ua)) {
            return [
                'verdict' => 'exploit_scanner',
                'score' => 99,
                'confidence' => 0.99,
                'probabilities' => ['exploit_scanner' => 0.95, 'aggressive_scraper' => 0.05],
                'reasons' => ['Matches known automated exploit/security scanner signature'],
            ];
        }

        if (preg_match('/(curl|wget|httpie|postman|insomnia)/i', $ua)) {
            return [
                'verdict' => 'benign_tool',
                'score' => 45,
                'confidence' => 0.92,
                'probabilities' => ['benign_tool' => 0.70, 'aggressive_scraper' => 0.25, 'legitimate_browser' => 0.05],
                'reasons' => ['Standard command-line or API developer tool signature'],
            ];
        }

        if (preg_match('/(python-requests|aiohttp|urllib|go-http-client|scrapy|beautifulsoup|apache-httpclient|java\/)/i', $ua)) {
            return [
                'verdict' => 'aggressive_scraper',
                'score' => 78,
                'confidence' => 0.90,
                'probabilities' => ['aggressive_scraper' => 0.80, 'benign_tool' => 0.15, 'exploit_scanner' => 0.05],
                'reasons' => ['Automated HTTP client or scraping library header detected'],
            ];
        }

        if (preg_match('/(googlebot|bingbot|yandexbot|duckduckbot|slurp|baiduspider|twitterbot|facebookexternalhit|slackbot|discordbot)/i', $ua)) {
            return [
                'verdict' => 'verified_crawler',
                'score' => 10,
                'confidence' => 0.95,
                'probabilities' => ['verified_crawler' => 0.92, 'legitimate_browser' => 0.08],
                'reasons' => ['Known search engine or social unfurler crawler signature'],
            ];
        }

        if (preg_match('/(headlesschrome|puppeteer|playwright|phantomjs|selenium)/i', $ua)) {
            return [
                'verdict' => 'aggressive_scraper',
                'score' => 88,
                'confidence' => 0.94,
                'probabilities' => ['aggressive_scraper' => 0.88, 'exploit_scanner' => 0.10],
                'reasons' => ['Headless browser / browser automation framework detected'],
            ];
        }

        return [
            'verdict' => 'legitimate_browser',
            'score' => 5,
            'confidence' => 0.90,
            'probabilities' => ['legitimate_browser' => 0.92, 'benign_tool' => 0.05, 'aggressive_scraper' => 0.03],
            'reasons' => ['Matches standard interactive consumer browser signature'],
        ];
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
