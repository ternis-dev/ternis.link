<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\SpamAppeal;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpamAppealController extends Controller
{
    /**
     * Show the spam appeal submission form.
     */
    public function create(Request $request)
    {
        $linkId = $request->query('link');
        $selectedLink = null;

        if ($linkId) {
            $user = $request->user();
            $query = Link::query();

            if ($user && ! $user->isAdmin()) {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                });
            }

            $selectedLink = $query->find($linkId);
        }

        // Gather all quarantined/flagged links owned by the user
        $flaggedLinks = $request->user()
            ? $request->user()->links()
                ->where('is_removed', false)
                ->where(function ($q) {
                    $q->where('is_active', false)
                        ->orWhereJsonContains('tags', 'quarantine')
                        ->orWhereJsonContains('tags', 'spam')
                        ->orWhereJsonContains('tags', 'phishing')
                        ->orWhereJsonContains('tags', 'malware');
                })
                ->with('domain')
                ->latest()
                ->get()
            : collect();

        $theme = $request->routeIs('public-dashboard.*') ? 'public' : 'dashboard';

        return view('appeals.create', [
            'selectedLink' => $selectedLink,
            'flaggedLinks' => $flaggedLinks,
            'contactEmail' => $request->user()?->email ?? '',
            'theme' => $theme,
        ]);
    }

    /**
     * Store a newly submitted spam appeal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'link_id' => ['required', 'string', 'exists:links,id'],
            'contact_email' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $link = Link::findOrFail($validated['link_id']);
        $user = $request->user();

        // Ensure user owns this link if it has an owner
        if ($link->user_id !== null && $user && $link->user_id !== $user->id && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'link_id' => 'You are not authorized to submit an appeal for this link.',
            ]);
        }

        // Prevent duplicate pending appeals for the same link
        $existingPending = SpamAppeal::where('link_id', $link->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw ValidationException::withMessages([
                'link_id' => 'An appeal for this link is already pending review.',
            ]);
        }

        $appeal = SpamAppeal::create([
            'link_id' => $link->id,
            'user_id' => $user?->id,
            'contact_email' => $validated['contact_email'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Notify admins of new appeal
        Notifier::admins(
            'New Spam Appeal Submitted',
            [
                "Appeal submitted by {$appeal->contact_email} for link /{$link->slug}.",
                "Target URL: {$link->destination_url}",
                'Explanation: '.Str::limit($appeal->reason, 200),
            ],
            DomainUrls::admin('/appeals'),
            'Review Spam Appeals'
        );

        $redirectRoute = $request->routeIs('public-dashboard.*')
            ? 'public-dashboard.links'
            : ($user ? 'dashboard.links' : 'home');

        return redirect()->route($redirectRoute)->with('success', 'Your spam appeal has been submitted and is under human review.');
    }
}
