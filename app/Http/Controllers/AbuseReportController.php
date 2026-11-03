<?php

namespace App\Http\Controllers;

use App\Models\AbuseReport;
use App\Models\Link;
use App\Support\DebugLinkReference;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AbuseReportController extends Controller
{
    /**
     * Show the public abuse report form (ternis.link/pages/abuse).
     */
    public function create(Request $request)
    {
        $url = (string) ($request->query('url') ?: $request->query('link') ?: '');

        return view('pages.abuse', [
            'url' => $url,
            'categories' => AbuseReport::CATEGORIES,
            'submitted' => (bool) $request->query('success', false),
        ]);
    }

    /**
     * Plain Markdown twin of the abuse report page (GET /pages/abuse.md).
     */
    public function createMd(Request $request)
    {
        $domains = implode(', ', array_keys(config('domains.map', [])));
        $content = <<<MARKDOWN
# Ternis Link Network — Abuse & Threat Reporting

> Canonical form: https://ternis.link/pages/abuse  
> Direct email: abuse@ternis.dev  
> Emergency / Law enforcement: legal@ternis.dev  

We take phishing, malware, spam, brand impersonation, and fraudulent short links seriously. Any abusive link operating on our network will be investigated and deactivated or quarantined.

## Supported Network Domains

This intake covers all links created across the Ternis network, including:
{$domains}

## Reporting Methods

1. **Web Submission Form**: https://ternis.link/pages/abuse
2. **Direct Email**: Send the offending URL, evidence, and affected brand/target to [abuse@ternis.dev](mailto:abuse@ternis.dev).
3. **Automated Submissions**: Send HTTP POST requests with JSON payload to `https://ternis.link/pages/abuse`.

## Categories Accepted

- **phishing**: Phishing, credential harvesting, impersonation of financial, social, or authentication portals.
- **malware**: Direct download links to malware, ransomware, spyware, or exploit kits.
- **spam**: Unsolicited bulk messaging, SMS spam, discord/telegram raid spam.
- **fraud**: Financial scams, investment fraud, fake support services.
- **copyright**: Trademark and intellectual property infringement.
- **harassment**: Doxxing, defamation, targeted harassment, or illegal material.
- **other**: Any other violation of the Ternis Acceptable Use Policy.

Reports are triaged by our automated defenses and human moderation team. Quarantined links are blocked from redirecting across the entire global routing network.
MARKDOWN;

        return response($content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
        ]);
    }

    /**
     * Store an incoming abuse report.
     */
    public function store(Request $request)
    {
        // Honeypot check for bots
        if ($request->filled('company_website')) {
            return redirect()->route('pages.abuse', ['success' => 1])
                ->with('success', 'Your report has been received.');
        }

        $validated = $request->validate([
            'reported_url' => ['required', 'string', 'max:2048'],
            'category' => ['required', 'string', Rule::in(array_keys(AbuseReport::CATEGORIES))],
            'reporter_email' => ['required', 'email', 'max:255'],
            'reporter_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $reportedUrl = trim($validated['reported_url']);
        $matchedLink = null;

        try {
            $matchedLink = DebugLinkReference::resolve($reportedUrl);
        } catch (\Throwable) {
            // If not directly resolvable via DebugLinkReference, check destination URL or slug
            $matchedLink = Link::with('domain')
                ->where('is_removed', false)
                ->where(function ($q) use ($reportedUrl) {
                    $q->where('destination_url', $reportedUrl)
                        ->orWhere('destination_url', rtrim($reportedUrl, '/'))
                        ->orWhere('slug', ltrim(parse_url($reportedUrl, PHP_URL_PATH) ?? '', '/'));
                })
                ->first();
        }

        $ipHash = hash('sha256', (string) $request->ip().config('app.key'));

        $report = AbuseReport::create([
            'link_id' => $matchedLink?->id,
            'reported_url' => $reportedUrl,
            'category' => $validated['category'],
            'reporter_email' => $validated['reporter_email'],
            'reporter_name' => $validated['reporter_name'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'ip_hash' => $ipHash,
        ]);

        // Notify admins
        Notifier::admins(
            "Abuse Report #{$report->id}: {$report->categoryLabel()}",
            [
                "Reported URL: {$report->reported_url}",
                "Reporter: {$report->reporter_email}".(! empty($report->reporter_name) ? " ({$report->reporter_name})" : ''),
                'Category: '.$report->categoryLabel(),
                'Details: '.Str::limit($report->reason, 200),
                $matchedLink ? "Matched system link: /{$matchedLink->slug} (ID: {$matchedLink->id})" : 'No direct link record matched.',
            ],
            DomainUrls::admin('/abuse-reports'),
            'View Abuse Reports'
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Your abuse report has been submitted. Our Trust & Safety team will review it promptly.',
                'report_id' => $report->id,
            ], 201);
        }

        return redirect()->route('pages.abuse', ['success' => 1])
            ->with('success', 'Your abuse report has been submitted. Our Trust & Safety team investigates all reports promptly.');
    }
}
