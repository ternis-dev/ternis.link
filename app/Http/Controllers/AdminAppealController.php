<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\SpamAppeal;
use App\Models\ThreatEvaluation;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Http\Request;

class AdminAppealController extends Controller
{
    /**
     * Display a listing of spam appeals for admin review.
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = SpamAppeal::with(['link.domain', 'link.threatEvaluations', 'user', 'reviewer']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $appeals = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => SpamAppeal::count(),
            'pending' => SpamAppeal::where('status', 'pending')->count(),
            'approved' => SpamAppeal::where('status', 'approved')->count(),
            'rejected' => SpamAppeal::where('status', 'rejected')->count(),
        ];

        return view('admin.appeals', compact('appeals', 'stats', 'status'));
    }

    /**
     * Approve a spam appeal and unquarantine/restore the link.
     */
    public function approve(Request $request, SpamAppeal $appeal)
    {
        if (! $appeal->isPending()) {
            return back()->with('error', 'This appeal has already been resolved.');
        }

        $link = $appeal->link;

        $appeal->update([
            'status' => 'approved',
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_notes' => $request->input('admin_notes'),
        ]);

        if ($link) {
            $tags = is_array($link->tags) ? $link->tags : [];
            $tags = array_values(array_diff($tags, ['quarantine', 'malware', 'phishing', 'spam']));
            if (! in_array('appeal_approved', $tags, true)) {
                $tags[] = 'appeal_approved';
            }

            $link->update([
                'is_active' => true,
                'tags' => $tags,
            ]);

            Link::forgetCachedSlug((string) $link->domain_id, (string) $link->slug);

            ThreatEvaluation::create([
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'creator_ip_hash' => $link->creator_ip_hash ?? '',
                'destination_url' => $link->destination_url,
                'destination_host' => (string) parse_url($link->destination_url, PHP_URL_HOST),
                'evaluation_type' => 'appeal_resolution',
                'verdict' => 'safe',
                'threat_score' => 0,
                'confidence' => 1.0,
                'action_taken' => 'cleared',
                'reasoning' => 'Spam appeal #'.$appeal->id.' approved by admin #'.$request->user()->id.($request->input('admin_notes') ? ': '.$request->input('admin_notes') : ''),
                'metadata' => [
                    'appeal_id' => $appeal->id,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now()->toIso8601String(),
                ],
            ]);
        }

        // Notify link owner
        if ($appeal->user) {
            Notifier::security(
                $appeal->user,
                'Spam Appeal Approved: Short Link Restored',
                [
                    "Your appeal for link \"/{$link->slug}\" pointing to \"{$link->destination_url}\" has been approved.",
                    'The quarantine has been removed and your link is active and accessible again.',
                ],
                DomainUrls::dashboard('/links/'.$link->id),
                'View Link'
            );
        }

        return back()->with('success', "Appeal approved. Link /{$link->slug} has been restored and unquarantined.");
    }

    /**
     * Reject a spam appeal and keep the link quarantined.
     */
    public function reject(Request $request, SpamAppeal $appeal)
    {
        if (! $appeal->isPending()) {
            return back()->with('error', 'This appeal has already been resolved.');
        }

        $notes = $request->input('admin_notes', 'Confirmed security or spam violation upon manual review.');

        $appeal->update([
            'status' => 'rejected',
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        if ($appeal->user) {
            Notifier::security(
                $appeal->user,
                'Spam Appeal Rejected: Link Remains Quarantined',
                [
                    "Your appeal for link \"/{$appeal->link?->slug}\" pointing to \"{$appeal->link?->destination_url}\" was reviewed and rejected.",
                    'Reviewer note: '.$notes,
                    'The link remains deactivated to protect visitors.',
                ]
            );
        }

        return back()->with('success', 'Appeal rejected. The link remains quarantined.');
    }
}
