<?php

namespace App\Http\Controllers;

use App\Models\AbuseReport;
use App\Models\Link;
use App\Models\ThreatEvaluation;
use Illuminate\Http\Request;

class AdminAbuseReportController extends Controller
{
    /**
     * Display a listing of abuse reports for admin triage.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $search = trim((string) $request->query('q', ''));

        $query = AbuseReport::with(['link.domain', 'reviewer']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('reported_url', 'like', "%{$search}%")
                    ->orWhere('reporter_email', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => AbuseReport::count(),
            'pending' => AbuseReport::where('status', 'pending')->count(),
            'investigating' => AbuseReport::where('status', 'investigating')->count(),
            'resolved' => AbuseReport::where('status', 'resolved')->count(),
            'dismissed' => AbuseReport::where('status', 'dismissed')->count(),
        ];

        return view('admin.abuse-reports', compact('reports', 'stats', 'status', 'search'));
    }

    /**
     * Mark an abuse report as investigating.
     */
    public function investigate(Request $request, AbuseReport $report)
    {
        $report->update([
            'status' => 'investigating',
            'reviewed_by_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Report #'.$report->id.' marked as investigating.');
    }

    /**
     * Resolve an abuse report and take optional moderation action.
     */
    public function resolve(Request $request, AbuseReport $report)
    {
        $action = $request->input('action_taken', 'quarantined');
        $notes = $request->input('admin_notes');

        $report->update([
            'status' => 'resolved',
            'action_taken' => $action,
            'admin_notes' => $notes,
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $link = $report->link;

        if ($link) {
            if ($action === 'quarantined') {
                $tags = is_array($link->tags) ? $link->tags : [];
                if (! in_array('quarantine', $tags, true)) {
                    $tags[] = 'quarantine';
                }
                $link->update([
                    'is_active' => false,
                    'tags' => $tags,
                ]);

                Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);

                ThreatEvaluation::create([
                    'link_id' => $link->id,
                    'user_id' => $link->user_id,
                    'creator_ip_hash' => $link->creator_ip_hash ?? '',
                    'destination_url' => $link->destination_url,
                    'destination_host' => (string) parse_url($link->destination_url, PHP_URL_HOST),
                    'evaluation_type' => 'abuse_report',
                    'verdict' => 'malicious',
                    'threat_score' => 100,
                    'confidence' => 1.0,
                    'action_taken' => 'quarantined',
                    'reasoning' => "Quarantined via abuse report #{$report->id} ({$report->category})".($notes ? ": {$notes}" : ''),
                    'metadata' => [
                        'report_id' => $report->id,
                        'resolved_by' => $request->user()->id,
                    ],
                ]);
            } elseif ($action === 'deactivated') {
                $link->update(['is_active' => false]);
                Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);
            }
        }

        return back()->with('success', "Abuse report #{$report->id} resolved (Action: {$action}).");
    }

    /**
     * Dismiss an abuse report without sanctioning the link.
     */
    public function dismiss(Request $request, AbuseReport $report)
    {
        $report->update([
            'status' => 'dismissed',
            'action_taken' => 'dismissed',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', "Abuse report #{$report->id} dismissed.");
    }
}
