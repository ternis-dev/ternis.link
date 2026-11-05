<?php

namespace App\Livewire\Concerns;

use App\Models\Link;
use App\Models\SpamAppeal;
use App\Services\AiThreatDetectionService;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;

trait ChecksCreatedLinkSafety
{
    #[Locked]
    public ?string $securityCheckLinkId = null;

    #[Locked]
    public ?string $blockedLinkId = null;

    public string $appealEmail = '';

    public string $appealReason = '';

    #[Locked]
    public bool $appealSubmitted = false;

    protected function resetSecurityAppeal(): void
    {
        $this->blockedLinkId = null;
        $this->appealEmail = '';
        $this->appealReason = '';
        $this->appealSubmitted = false;
        $this->resetValidation(['appealEmail', 'appealReason']);
    }

    public function submitSecurityAppeal(): void
    {
        // The locked ID is set only for the link created by this form.
        $link = $this->blockedLinkId ? Link::find($this->blockedLinkId) : null;
        if (! $link || $link->user_id !== null || $link->is_active || $this->appealSubmitted) {
            return;
        }

        $this->validate([
            'appealEmail' => ['required', 'email', 'max:255'],
            'appealReason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $key = 'guest-security-appeal:'.hash('sha256', (string) request()->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('appealReason', 'Too many appeals. Please try again later.');

            return;
        }

        RateLimiter::hit($key, 3600);
        $appeal = SpamAppeal::firstOrCreate(
            ['link_id' => $link->id, 'status' => 'pending'],
            ['user_id' => null, 'contact_email' => $this->appealEmail, 'reason' => $this->appealReason],
        );
        $this->appealSubmitted = true;

        if ($appeal->wasRecentlyCreated) {
            Notifier::admins(
                'New Spam Appeal Submitted',
                ['Anonymous creator requested a review of /'.$link->slug.'.', 'Target URL: '.$link->destination_url],
                DomainUrls::admin('/appeals'),
                'Review Spam Appeals',
            );
        }
    }

    public function checkCreatedLinkSafety(): void
    {
        if ($this->securityCheckLinkId === null) {
            return;
        }

        $link = Link::find($this->securityCheckLinkId);

        if (! $link || ! $link->is_active) {
            $this->blockedLinkId = $link?->id;
            $this->securityCheckLinkId = null;
            $this->destination_url = $this->originalUrl ?? '';
            $this->shortUrl = null;
            $this->originalUrl = null;
            $this->errorKind = 'security';
            $this->urlState = 'invalid';
            $this->addError('destination_url', $this->securityBlockMessage());

            return;
        }

        if ($link->threatEvaluations()->exists() || ! app(AiThreatDetectionService::class)->shouldEvaluateLink($link)) {
            $this->securityCheckLinkId = null;
        }
    }
}
