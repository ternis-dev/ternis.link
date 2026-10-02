<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\BioPage;
use App\Services\BioService;
use App\Support\Activity;
use App\Support\DomainUrls;
use App\Support\Notifier;
use Livewire\Component;
use Livewire\WithPagination;

class BioModeration extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function deactivate(string $pageId, BioService $bio): void
    {
        $this->ensureAdmin();

        $page = BioPage::findOrFail($pageId);
        $page->update(['is_active' => false]);
        $bio->forgetCaches($page);

        Activity::record(ActivityLog::ADMIN_BIO_DEACTIVATED, auth()->user(), $page, [
            'title' => $page->title,
        ]);

        if ($page->user) {
            Notifier::security(
                $page->user,
                'Your bio page was deactivated',
                ["The bio page {$page->title} was deactivated by an administrator. Analytics are preserved."],
                DomainUrls::dashboard('/bio'),
                'View your bio pages',
            );
        }
    }

    public function reactivate(string $pageId, BioService $bio): void
    {
        $this->ensureAdmin();

        $page = BioPage::findOrFail($pageId);
        $page->update(['is_active' => true]);
        $bio->forgetCaches($page);

        Activity::record(ActivityLog::ADMIN_BIO_REACTIVATED, auth()->user(), $page, [
            'title' => $page->title,
        ]);
    }

    public function remove(string $pageId, BioService $bio): void
    {
        $this->ensureAdmin();

        $page = BioPage::findOrFail($pageId);
        $page->update(['is_removed' => true]);
        $bio->forgetCaches($page);

        Activity::record(ActivityLog::ADMIN_BIO_REMOVED, auth()->user(), $page, [
            'title' => $page->title,
        ]);

        if ($page->user) {
            Notifier::security(
                $page->user,
                'Your bio page was removed',
                ["The bio page {$page->title} was removed by an administrator. It no longer resolves, but its stats are preserved."],
                DomainUrls::dashboard('/bio'),
                'View your bio pages',
            );
        }
    }

    public function restore(string $pageId, BioService $bio): void
    {
        $this->ensureAdmin();

        $page = BioPage::findOrFail($pageId);
        $page->update(['is_removed' => false]);
        $bio->forgetCaches($page);

        Activity::record(ActivityLog::ADMIN_BIO_RESTORED, auth()->user(), $page, [
            'title' => $page->title,
        ]);
    }

    public function render(BioService $bio)
    {
        $pages = BioPage::with(['domain:id,hostname', 'user:id,name,email'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($qq) {
                $qq->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            }))
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true)->where('is_removed', false))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->status === 'removed', fn ($q) => $q->where('is_removed', true))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.bio-moderation', compact('pages'));
    }

    private function ensureAdmin(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }
    }
}
