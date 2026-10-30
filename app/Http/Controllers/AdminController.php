<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Click;
use App\Models\Domain;
use App\Models\ErrorEncounter;
use App\Models\Link;
use App\Models\QrGeneration;
use App\Models\ThreatEvaluation;
use App\Models\User;
use App\Services\AdminStatsService;
use App\Services\AiThreatDetectionService;
use App\Services\LinkInsights;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Http\Request;
use TypeSafeAI\SystemOneRequest;

class AdminController extends Controller
{
    /**
     * System overview — totals, today, 30d charts & heatmaps, top links.
     */
    public function index(Request $request, AdminStatsService $statsService)
    {
        $stats = [
            'total_users' => User::count(),
            'total_links' => Link::count(),
            'active_links' => Link::where('is_active', true)->where('is_removed', false)->count(),
            'removed_links' => Link::where('is_removed', true)->count(),
            'total_clicks' => Click::count(),
            'clicks_today' => Click::where('created_at', '>=', now()->startOfDay())->count(),
            'links_today' => Link::where('created_at', '>=', now()->startOfDay())->count(),
            'direct_url_clicks' => Click::count() > 0 ? Click::where('is_direct_url', true)->count() : 0,
            'total_domains' => Domain::count(),
            'active_api_keys' => ApiKey::whereNull('revoked_at')->count(),
            'qr_codes' => QrGeneration::count(),
            'errors_today' => ErrorEncounter::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        $telemetry = $statsService->getOverviewStats(['days' => 30]);

        $topLinks = Link::notRemoved()
            ->with(['domain', 'user'])
            ->orderByDesc('click_count')
            ->limit(5)
            ->get();

        $recentLinks = Link::notRemoved()
            ->with(['domain', 'user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('admin.index', compact('stats', 'telemetry', 'topLinks', 'recentLinks'));
    }

    /**
     * System-wide link insights and telemetry heatmap.
     */
    public function insights(Request $request)
    {
        return redirect()->route('admin.stats.show', array_merge(['section' => 'insights'], $request->query()));
    }

    /**
     * Build the filtered threat evaluations query for inspection and export.
     */
    protected function filteredThreatsQuery(Request $request)
    {
        $query = ThreatEvaluation::query()->with(['link.domain', 'link.apiKey.user', 'user']);

        if ($verdict = $request->query('verdict')) {
            $query->where('verdict', $verdict);
        }

        if ($action = $request->query('action')) {
            $query->where('action_taken', $action);
        }

        if ($type = $request->query('type')) {
            $query->where('evaluation_type', $type);
        }

        if ($request->boolean('bypassed')) {
            $query->whereHas('link.apiKey', fn ($q) => $q->where('bypass_ai_check', true));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('destination_url', 'like', "%{$search}%")
                    ->orWhere('destination_host', 'like', "%{$search}%")
                    ->orWhere('reasoning', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * TypeSafe AI Threat Inspections & Sandbox (Admin).
     */
    public function threats(Request $request, AiThreatDetectionService $threatService)
    {
        $evaluations = $this->filteredThreatsQuery($request)->latest()->paginate(25)->withQueryString();

        $stats = [
            'total_evaluations' => ThreatEvaluation::count(),
            'total_quarantined' => ThreatEvaluation::where('action_taken', 'quarantined')->count(),
            'total_bot_protected' => ThreatEvaluation::where('action_taken', 'bot_protected')->count(),
            'total_cleared' => ThreatEvaluation::where('action_taken', 'cleared')->count(),
            'avg_score' => (int) round((float) ThreatEvaluation::avg('threat_score')),
            'model_name' => config('ai_security.model', 'jev-latest'),
            'ai_enabled' => $threatService->isEnabled() && $threatService->getClient() !== null,
            'bypassed_admin_keys' => ApiKey::where('bypass_ai_check', true)->whereHas('user', fn ($q) => $q->where('role', 'admin'))->count(),
        ];

        return view('admin.threats', compact('evaluations', 'stats'));
    }

    /**
     * Export threat evaluations and security audit logs in CSV or JSON format.
     */
    public function exportThreats(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'csv'));
        $dateStr = now()->format('Y-m-d');
        $query = $this->filteredThreatsQuery($request)->latest();

        $escapeFormula = static function (mixed $value): string {
            $str = (string) ($value ?? '');
            if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$str;
            }

            return $str;
        };

        if ($format === 'json') {
            $filename = "threat-security-audit-{$dateStr}.json";
            $evaluations = $query->cursor();

            return response()->streamDownload(function () use ($evaluations) {
                $out = fopen('php://output', 'w');
                fwrite($out, "[\n");
                $first = true;

                foreach ($evaluations as $eval) {
                    if (! $first) {
                        fwrite($out, ",\n");
                    }
                    $first = false;

                    $record = [
                        'id' => $eval->id,
                        'timestamp' => $eval->created_at?->toIso8601String(),
                        'destination_url' => $eval->destination_url,
                        'destination_host' => $eval->destination_host,
                        'evaluation_type' => $eval->evaluation_type,
                        'verdict' => $eval->verdict,
                        'threat_score' => $eval->threat_score,
                        'confidence' => $eval->confidence,
                        'action_taken' => $eval->action_taken,
                        'reasoning' => $eval->reasoning,
                        'link_id' => $eval->link_id,
                        'link_slug' => $eval->link?->slug,
                        'creator_ip_hash' => $eval->creator_ip_hash,
                        'submitter' => $eval->user?->email ?? ($eval->link?->user?->email ?? 'Anonymous / Guest'),
                        'api_key_bypassed' => (bool) $eval->link?->apiKey?->bypass_ai_check,
                        'metadata' => $eval->metadata,
                    ];

                    fwrite($out, '  '.json_encode($record, JSON_UNESCAPED_SLASHES));
                }

                fwrite($out, "\n]\n");
                fclose($out);
            }, $filename, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, private',
            ]);
        }

        $filename = "threat-security-audit-{$dateStr}.csv";
        $evaluations = $query->cursor();

        return response()->streamDownload(function () use ($evaluations, $escapeFormula) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Evaluation ID',
                'Timestamp',
                'Destination URL',
                'Destination Host',
                'Evaluation Type',
                'Verdict',
                'Threat Score',
                'Confidence',
                'Action Taken',
                'Reasoning',
                'Link ID',
                'Link Slug',
                'Creator IP Hash',
                'Submitter',
                'API Key AI Bypassed',
            ]);

            foreach ($evaluations as $eval) {
                fputcsv($out, [
                    $escapeFormula($eval->id),
                    $eval->created_at?->toIso8601String(),
                    $escapeFormula($eval->destination_url),
                    $escapeFormula($eval->destination_host),
                    $escapeFormula($eval->evaluation_type),
                    $escapeFormula($eval->verdict),
                    $eval->threat_score,
                    $eval->confidence,
                    $escapeFormula($eval->action_taken),
                    $escapeFormula($eval->reasoning),
                    $escapeFormula($eval->link_id),
                    $escapeFormula($eval->link?->slug),
                    $eval->creator_ip_hash,
                    $escapeFormula($eval->user?->email ?? ($eval->link?->user?->email ?? 'Anonymous / Guest')),
                    $eval->link?->apiKey?->bypass_ai_check ? 'YES' : 'NO',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Unquarantine and restore a flagged link directly from the threats inspector.
     */
    public function unquarantineLink(Link $link)
    {
        $tags = is_array($link->tags) ? $link->tags : [];
        $tags = array_values(array_diff($tags, ['quarantine', 'malware', 'phishing', 'spam']));
        if (! in_array('admin_cleared', $tags, true)) {
            $tags[] = 'admin_cleared';
        }

        $link->update([
            'is_active' => true,
            'tags' => $tags,
        ]);

        Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);

        Activity::record(ActivityLog::ADMIN_LINK_REACTIVATED, auth()->user(), $link, [
            'slug' => $link->slug,
            'reason' => 'Threat quarantine cleared by admin inspector',
        ]);

        if ($link->user) {
            Notifier::security(
                $link->user,
                'Security Status Updated: Link Restored',
                [
                    "Your link \"/{$link->slug}\" pointing to \"{$link->destination_url}\" has been reviewed by an administrator and restored.",
                    'The quarantine status has been removed and the link is active again.',
                ],
                DomainUrls::dashboard('/links/'.$link->id),
                'View Link'
            );
        }

        return back()->with('success', "Link /{$link->slug} unquarantined and reactivated successfully.");
    }

    /**
     * Re-evaluate a link on demand through TypeSafe AI.
     */
    public function rescanLink(Link $link, AiThreatDetectionService $threatService)
    {
        $evaluation = $threatService->evaluateLink($link, [
            'force' => true,
            'skip_burst_check' => true,
            'evaluation_type' => 'admin_rescan',
        ]);

        if ($evaluation === null) {
            return back()->with('error', "Could not rescan link /{$link->slug} (AI client unavailable or disabled).");
        }

        return back()->with('success', "Rescanned link /{$link->slug}: Verdict {$evaluation->verdict} (Score: {$evaluation->threat_score}/100, Action: {$evaluation->action_taken}).");
    }

    /**
     * Live manual inspection sandbox for testing any URL against TypeSafe AI.
     */
    public function testThreatAi(Request $request, AiThreatDetectionService $threatService)
    {
        $request->validate([
            'test_url' => ['required', 'url', 'max:2048'],
        ]);

        $url = (string) $request->input('test_url');
        $host = (string) parse_url($url, PHP_URL_HOST);

        $audit = [
            'url' => $url, 'host' => $host, 'category' => 'unverified',
            'threat_score' => 0, 'confidence' => 0,
            'reasoning' => 'TypeSafe AI client unavailable.',
        ];
        $client = $threatService->getClient();

        if ($client === null) {
            $threatService->recordUrlScan($audit, 'admin_url_scan');

            return back()->with('test_result', [
                'error' => 'TypeSafe AI client is not configured or disabled.',
            ]);
        }

        $model = config('ai_security.model', 'jev-latest');
        $state = implode("\n", [
            'Destination URL: '.$url,
            'Host: '.$host,
            'Title: Admin Sandbox Live Inspection',
            'Description: Manual test query from admin threat console',
            'Account Context: Admin manual triage',
            'User-Agent: '.($request->userAgent() ?? 'AdminConsole/1.0'),
        ]);

        try {
            $s1Request = SystemOneRequest::build($state, $model)
                ->choice('verdict', 'Analyze the destination URL and metadata for spam, phishing, deception, or malicious behavior.', [
                    'safe' => 'Benign, legitimate website or service',
                    'spam' => 'Low-quality affiliate spam, SEO link farming, deceptive marketing, junk redirects',
                    'phishing' => 'Credential harvesting, brand impersonation, deceptive login portal, fake verification',
                    'malware' => 'Drive-by download, trojan/exploit payload distribution, suspicious binary bundle',
                ])
                ->score('threat_score', 'Threat probability score from 0 (benign) to 100 (critical malicious)', [
                    'Benign, legitimate website',
                    'Mildly suspicious or low reputation',
                    'Borderline or suspected promotional spam',
                    'High probability of phishing or scam',
                    'Critical confirmed malware or credential harvester',
                ]);

            $result = $client->systemOne($s1Request);

            $verdictAnswer = $result->choice('verdict');
            $scoreAnswer = $result->score('threat_score');

            $maxLevel = max(1, count($scoreAnswer->legend ?? []) - 1);
            $rawScore = $scoreAnswer->score ?? 0.0;
            $threatScore = (int) round(min(100, max(0, ($rawScore / $maxLevel) * 100)));

            $audit = array_merge($audit, [
                'category' => $verdictAnswer->choice ?? 'safe',
                'threat_score' => $threatScore,
                'confidence' => $verdictAnswer->confidence ?? 1.0,
                'reasoning' => 'Manual admin URL threat inspection.',
            ]);

            return back()->with('test_result', [
                'url' => $url,
                'host' => $host,
                'model' => $model,
                'verdict' => $verdictAnswer->choice ?? 'safe',
                'confidence' => $verdictAnswer->confidence ?? 1.0,
                'probabilities' => $verdictAnswer->probabilities ?? [],
                'threat_score' => $threatScore,
                'raw_score' => $rawScore,
                'score_probabilities' => $scoreAnswer->probabilities ?? [],
                'score_legend' => $scoreAnswer->legend ?? [],
                'prompt_state' => $state,
            ]);
        } catch (\Throwable $e) {
            $audit['reasoning'] = 'TypeSafe AI scanner error.';

            return back()->with('test_result', [
                'error' => 'TypeSafe AI error: '.$e->getMessage(),
            ]);
        } finally {
            $threatService->recordUrlScan($audit, 'admin_url_scan');
        }
    }

    /**
     * Link moderation page (Livewire: Admin\LinkModeration).
     */
    public function links(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            Link::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.links', compact('heatmap'));
    }

    /**
     * User management page (Livewire: Admin\UserTable).
     */
    public function users(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            User::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.users', compact('heatmap'));
    }

    /**
     * Domain moderation page (Livewire: Admin\DomainModeration).
     */
    public function domains()
    {
        return view('admin.domains');
    }

    /**
     * Audit log page (Livewire: Admin\ActivityLogTable).
     */
    public function activity(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            ActivityLog::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.activity', compact('heatmap'));
    }

    /**
     * Bio page moderation (Livewire: Admin\BioModeration).
     */
    public function bio()
    {
        return view('admin.bio');
    }

    /**
     * Error encounters page (Livewire: Admin\ErrorEncounterTable).
     */
    public function errors(LinkInsights $insights)
    {
        $heatmap = $insights->weekdayHourGrid(
            ErrorEncounter::query()->where('created_at', '>=', now()->subDays(30)->startOfDay())
        );

        return view('admin.errors', compact('heatmap'));
    }

    /**
     * Allowed pairs and rate limit allowlist (Livewire: Admin\AllowedClientModeration).
     */
    public function allowedClients()
    {
        return view('admin.allowed-clients');
    }
}
