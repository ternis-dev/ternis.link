<?php

namespace App\Livewire\Dashboard;

use App\Jobs\BuildPrivacyExport;
use App\Models\PrivacyExport;
use App\Services\AccountErasureService;
use Livewire\Component;

/**
 * Privacy self-service: data summary, full export (async ZIP with
 * polling + download), and self-serve account deletion with a
 * 14-day grace period. Deletion scheduling requires a fresh SSO
 * login (younger than 15 minutes) plus typed confirmation.
 */
class PrivacySettings extends Component
{
    public string $confirmText = '';

    public bool $acknowledged = false;

    public function export(): void
    {
        $user = auth()->user();

        if ($user->privacyExports()->whereIn('status', ['pending', 'processing'])->exists()) {
            $this->addError('export', 'An export is already running.');

            return;
        }

        $export = PrivacyExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'expires_at' => now()->addDays((int) config('privacy.export_ttl_days', 7)),
        ]);

        BuildPrivacyExport::dispatch($export->id);
    }

    public function scheduleDeletion(AccountErasureService $erasure): void
    {
        $user = auth()->user();

        if (! $this->freshLogin()) {
            $this->addError('confirmText', 'Please sign out and sign back in — deletion needs a login from the last 15 minutes.');

            return;
        }

        if (! $this->acknowledged || trim($this->confirmText) !== 'DELETE-ME') {
            $this->addError('confirmText', 'Type DELETE-ME and tick the acknowledgement to continue.');

            return;
        }

        $erasure->schedule($user);
        $this->reset(['confirmText', 'acknowledged']);

        $this->dispatch('notify', message: 'Account deletion scheduled.', type: 'error');
    }

    public function cancelDeletion(AccountErasureService $erasure): void
    {
        $erasure->cancel(auth()->user());

        $this->dispatch('notify', message: 'Scheduled deletion cancelled — welcome back.', type: 'success');
    }

    private function freshLogin(): bool
    {
        $at = session('sso_login_at');

        if (! is_string($at) || $at === '') {
            return false;
        }

        try {
            return now()->diffInMinutes(new \DateTimeImmutable($at), true) <= 15;
        } catch (\Throwable) {
            return false;
        }
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.dashboard.privacy-settings', [
            'summary' => [
                'links' => $user->links()->count(),
                'domains' => $user->domains()->count(),
                'api_keys' => $user->apiKeys()->count(),
            ],
            'retention' => config('privacy'),
            'exports' => $user->privacyExports()->orderByDesc('created_at')->limit(5)->get(),
            'deletionAt' => $user->deletion_requested_at,
            'graceDays' => (int) config('privacy.deletion_grace_days', 14),
        ]);
    }
}
