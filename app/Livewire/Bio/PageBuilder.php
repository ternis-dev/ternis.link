<?php

namespace App\Livewire\Bio;

use App\Models\BioButton;
use App\Models\BioPage;
use App\Models\Domain;
use App\Services\BioService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PageBuilder extends Component
{
    public ?string $editingPageId = null;

    public ?string $domain_id = null;

    public string $title = '';

    public ?string $bio = null;

    public ?string $avatar_url = null;

    public string $theme = 'minimal';

    public ?string $accent = null;

    public ?string $parent_id = null;

    public string $slug = '';

    /** @var list<array{label: string, sublabel: ?string, kind: string, destination_url: ?string, icon: ?string, sort_order: int, is_active: bool}> */
    public array $buttons = [];

    public string $newLabel = '';

    public string $newUrl = '';

    public string $newKind = 'link';

    public function mount(): void
    {
        $first = $this->eligibleDomains()->first();
        if ($first) {
            $this->domain_id = $first->id;
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'theme' => ['required', 'in:minimal,dark,paper'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'slug' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function selectPage(string $pageId): void
    {
        $page = $this->ownedPage($pageId);

        if (! $page) {
            return;
        }

        $this->editingPageId = $page->id;
        $this->title = $page->title;
        $this->bio = $page->bio;
        $this->avatar_url = $page->avatar_url;
        $this->theme = $page->theme;
        $this->accent = $page->accent;
        $this->resetValidation();
    }

    public function createRoot(BioService $bio): void
    {
        $this->validateOnly('title');

        $domain = $this->ownedDomain((string) $this->domain_id);

        if (! $domain) {
            $this->addError('domain_id', 'Choose one of your verified domains.');

            return;
        }

        try {
            $page = $bio->createPage(auth()->user(), $domain, [
                'title' => $this->title !== '' ? $this->title : 'Links',
                'bio' => $this->bio,
                'avatar_url' => $this->avatar_url,
                'theme' => $this->theme,
                'accent' => $this->accent,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field === 'domain_id' ? 'domain_id' : 'title', $message);
                }
            }

            return;
        }

        $this->reset(['title', 'bio', 'avatar_url', 'slug', 'parent_id']);
        $this->theme = 'minimal';
        $this->selectPage($page->id);
    }

    public function createSub(BioService $bio): void
    {
        $this->validate([
            'parent_id' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]{1,64}$/'],
            'title' => ['required', 'string', 'max:80'],
        ]);

        $parent = $this->ownedPage((string) $this->parent_id);

        if (! $parent || $parent->parent_id !== null) {
            $this->addError('parent_id', 'Sub-pages hang off a root page.');

            return;
        }

        try {
            $page = $bio->createPage(auth()->user(), $parent->domain, [
                'slug' => strtolower($this->slug),
                'title' => $this->title,
                'bio' => $this->bio,
                'theme' => $this->theme,
            ], $parent);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field === 'slug' ? 'slug' : 'title', $message);
                }
            }

            return;
        }

        $this->reset(['slug', 'title', 'bio']);
        $this->selectPage($page->id);
    }

    public function savePage(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $this->validate();

        $page->update([
            'title' => $this->title,
            'bio' => $this->bio ?: null,
            'avatar_url' => $this->avatar_url ?: null,
            'theme' => $this->theme,
            'accent' => $this->accent ?: null,
        ]);
        $bio->forgetCaches($page->fresh());
    }

    public function addButton(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $this->validate([
            'newLabel' => ['required_unless:newKind,divider', 'string', 'max:60'],
            'newUrl' => ['required_if:newKind,link', 'required_if:newKind,social', 'nullable', 'url', 'max:2048'],
            'newKind' => ['required', 'in:link,header,divider,social'],
        ]);

        $current = $page->buttons()->orderBy('sort_order')->get()->map(fn (BioButton $b) => [
            'label' => $b->label,
            'sublabel' => $b->sublabel,
            'kind' => $b->kind,
            'destination_url' => $b->destination_url,
            'icon' => $b->icon,
            'sort_order' => $b->sort_order,
            'is_active' => $b->is_active,
        ])->all();

        $current[] = [
            'label' => $this->newLabel !== '' ? $this->newLabel : '—',
            'kind' => $this->newKind,
            'destination_url' => $this->newUrl !== '' ? $this->newUrl : null,
            'sort_order' => count($current),
            'is_active' => true,
        ];

        try {
            $bio->syncButtons($page, $current, auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError('newUrl', $message);
                }
            }

            return;
        }

        $this->reset(['newLabel', 'newUrl']);
        $this->newKind = 'link';
    }

    public function removeButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $current = $page->buttons()->orderBy('sort_order')->where('id', '!=', $buttonId)->get()->map(fn (BioButton $b) => [
            'label' => $b->label,
            'sublabel' => $b->sublabel,
            'kind' => $b->kind,
            'destination_url' => $b->destination_url,
            'icon' => $b->icon,
            'sort_order' => $b->sort_order,
            'is_active' => $b->is_active,
        ])->values()->all();

        $bio->syncButtons($page, $current, auth()->user());
    }

    public function deactivatePage(BioService $bio, string $pageId): void
    {
        $page = $this->ownedPage($pageId);

        if (! $page) {
            return;
        }

        $page->update(['is_active' => false]);
        $bio->forgetCaches($page);

        if ($this->editingPageId === $pageId) {
            $this->editingPageId = null;
        }
    }

    private function ownedPage(string $pageId): ?BioPage
    {
        return auth()->user()->bioPages()->with(['domain:id,hostname', 'buttons', 'children'])->find($pageId);
    }

    private function ownedDomain(string $domainId): ?Domain
    {
        return auth()->user()->domains()->where('domains.id', $domainId)->where('domains.is_active', true)->first();
    }

    private function eligibleDomains()
    {
        return auth()->user()->domains()
            ->where('domains.is_active', true)
            ->whereNotNull('domains.verified_at')
            ->orderBy('hostname')
            ->get();
    }

    public function render()
    {
        $pages = auth()->user()->bioPages()->with(['domain:id,hostname', 'children'])->orderByDesc('created_at')->get();
        $domains = $this->eligibleDomains();
        $editing = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        return view('livewire.bio.page-builder', compact('pages', 'domains', 'editing'));
    }
}
