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
     * TypeSafe AI Threat Inspections & Sandbox (Admin).
     */
    public function threats(Request $request, AiThreatDetectionService $threatService)
    {
        $query = ThreatEvaluation::query()->with(['link.domain', 'user']);

        if ($verdict = $request->query('verdict')) {
            $query->where('verdict', $verdict);
        }

        if ($action = $request->query('action')) {
            $query->where('action_taken', $action);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('destination_url', 'like', "%{$search}%")
                    ->orWhere('destination_host', 'like', "%{$search}%")
                    ->orWhere('reasoning', 'like', "%{$search}%");
            });
        }

        $evaluations = $query->latest()->paginate(25)->withQueryString();

        $stats = [
            'total_evaluations' => ThreatEvaluation::count(),
            'total_quarantined' => ThreatEvaluation::where('action_taken', 'quarantined')->count(),
            'total_bot_protected' => ThreatEvaluation::where('action_taken', 'bot_protected')->count(),
            'total_cleared' => ThreatEvaluation::where('action_taken', 'cleared')->count(),
            'avg_score' => (int) round((float) ThreatEvaluation::avg('threat_score')),
            'model_name' => config('ai_security.model', 'jev-latest'),
            'ai_enabled' => $threatService->isEnabled() && $threatService->getClient() !== null,
        ];

        return view('admin.threats', compact('evaluations', 'stats'));
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

        $client = $threatService->getClient();

        if ($client === null) {
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
            return back()->with('test_result', [
                'error' => 'TypeSafe AI error: '.$e->getMessage(),
            ]);
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
