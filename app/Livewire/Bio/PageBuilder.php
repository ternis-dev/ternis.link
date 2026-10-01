<?php

namespace App\Livewire\Bio;

use App\Models\BioPage;
use App\Models\Domain;
use App\Services\BioService;
use Illuminate\Support\Facades\URL;
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

    public string $locale = 'en';

    public ?string $accent = null;

    public ?string $theme_color = null;

    public string $button_style = 'filled';

    public ?string $page_password = null;

    public ?string $parent_id = null;

    public string $slug = '';

    public string $subTitle = '';

    /** @var list<array{label: string, sublabel: ?string, kind: string, destination_url: ?string, icon: ?string, sort_order: int, is_active: bool}> */
    public array $buttons = [];

    public string $newLabel = '';

    public ?string $newSublabel = null;

    public string $newUrl = '';

    public string $newKind = 'link';

    public ?string $newIcon = null;

    public ?string $newThumbnail = null;

    public string $newAction = 'url';

    public ?string $newTargetPage = null;

    public ?string $newModalTitle = null;

    public ?string $newModalBody = null;

    public ?string $newStartsAt = null;

    public ?string $newEndsAt = null;

    public ?string $published_at = null;

    public ?string $draftUrl = null;

    public ?string $draftExpires = null;

    public function mount(): void
    {
        $first = $this->eligibleDomains()->first();
        if ($first) {
            $this->domain_id = $first->id;
        }

        // Deep-link from the stats page ("Open in page-builder").
        $edit = request()->query('edit');
        if (is_string($edit) && $edit !== '' && $this->ownedPage($edit)) {
            $this->selectPage($edit);
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:280'],
            'avatar_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'theme' => ['required', 'in:minimal,dark,paper'],
            'locale' => ['required', 'in:en,de,fr,es,it'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'button_style' => ['required', 'in:filled,outline,soft'],
            'page_password' => ['nullable', 'string', 'min:8', 'max:72'],
            'slug' => ['nullable', 'string', 'max:64'],
            'published_at' => ['nullable', 'date'],
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
        $this->locale = $page->locale ?? 'en';
        $this->accent = $page->accent;
        $this->theme_color = $page->theme_color;
        $this->button_style = $page->button_style ?? 'filled';
        $this->page_password = null;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->reset(['slug', 'subTitle', 'parent_id', 'draftUrl', 'draftExpires']);
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
        // The sub-page form lives inside the editing card, so the
        // edited root is the parent unless explicitly overridden.
        if (($this->parent_id === null || trim($this->parent_id) === '') && $this->editingPageId !== null) {
            $this->parent_id = $this->editingPageId;
        }

        $this->validate([
            'parent_id' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]{1,64}$/'],
            'subTitle' => ['required', 'string', 'max:80'],
        ]);

        $parent = $this->ownedPage((string) $this->parent_id);

        if (! $parent || $parent->parent_id !== null) {
            $this->addError('parent_id', 'Sub-pages hang off a root page.');

            return;
        }

        try {
            $page = $bio->createPage(auth()->user(), $parent->domain, [
                'slug' => strtolower($this->slug),
                'title' => $this->subTitle,
                'bio' => $this->bio,
                'theme' => $this->theme,
            ], $parent);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field === 'slug' ? 'slug' : 'subTitle', $message);
                }
            }

            return;
        }

        $this->reset(['slug', 'subTitle']);
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
            'locale' => $this->locale,
            'accent' => $this->accent ?: null,
            'theme_color' => $this->theme_color ?: null,
            'button_style' => $this->button_style,
            'published_at' => $this->published_at ? new \DateTime($this->published_at) : null,
        ]);

        if ($this->page_password !== null && trim($this->page_password) !== '') {
            $bio->setPassword($page->fresh(), $this->page_password);
        }

        $this->page_password = null;
        $bio->forgetCaches($page->fresh());
    }

    public function removePassword(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $bio->clearPassword($page);
    }

    public function addButton(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $this->validate([
            'newLabel' => ['required_unless:newKind,divider', 'string', 'max:60'],
            'newSublabel' => ['nullable', 'string', 'max:120'],
            'newUrl' => ['nullable', 'url', 'max:2048'],
            'newKind' => ['required', 'in:link,header,divider,social'],
            'newIcon' => ['nullable', 'in:instagram,tiktok,x,youtube,github,globe,mail,link'],
            'newThumbnail' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'newAction' => ['required', 'in:url,subpage,modal'],
            'newTargetPage' => ['required_if:newAction,subpage', 'nullable', 'string'],
            'newModalTitle' => ['required_if:newAction,modal', 'nullable', 'string', 'max:80'],
            'newModalBody' => ['nullable', 'string', 'max:1000'],
            'newStartsAt' => ['nullable', 'date'],
            'newEndsAt' => ['nullable', 'date', 'after:newStartsAt'],
        ]);

        // URL is only required for plain URL buttons — sub-page and
        // modal buttons carry their target in dedicated fields.
        if (in_array($this->newKind, ['link', 'social'], true)
            && $this->newAction === 'url'
            && trim($this->newUrl) === '') {
            $this->addError('newUrl', 'A URL is required for link buttons.');

            return;
        }

        $current = $this->buttonRows($page);

        $current[] = [
            'label' => $this->newLabel !== '' ? $this->newLabel : '—',
            'sublabel' => $this->newSublabel ?: null,
            'kind' => $this->newKind,
            'action' => $this->newAction,
            'destination_url' => $this->newUrl !== '' ? $this->newUrl : null,
            'target_page_id' => $this->newTargetPage ?: null,
            'modal_title' => $this->newModalTitle ?: null,
            'modal_body' => $this->newModalBody ?: null,
            'icon' => $this->newIcon ?: null,
            'thumbnail_url' => $this->newThumbnail ?: null,
            'sort_order' => count($current),
            'is_active' => true,
            'starts_at' => $this->newStartsAt ?: null,
            'ends_at' => $this->newEndsAt ?: null,
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

        $this->reset(['newLabel', 'newSublabel', 'newUrl', 'newIcon', 'newThumbnail', 'newStartsAt', 'newEndsAt', 'newTargetPage', 'newModalTitle', 'newModalBody']);
        $this->newKind = 'link';
        $this->newAction = 'url';
    }

    public function toggleButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $current = collect($this->buttonRows($page))->map(
            fn (array $row) => $row['id'] === $buttonId ? [...$row, 'is_active' => ! $row['is_active']] : $row
        )->all();

        $bio->syncButtons($page, $current, auth()->user());
    }

    public function moveButton(BioService $bio, string $buttonId, string $direction): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page || ! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $ids = $page->buttons()->orderBy('sort_order')->pluck('id')->all();
        $index = array_search($buttonId, $ids, true);

        if ($index === false) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($ids[$swap])) {
            return;
        }

        [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];

        $bio->reorderButtons($page, $ids, auth()->user());
    }

    public function reorder(BioService $bio, array $orderedIds): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        try {
            $bio->reorderButtons($page, $orderedIds, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('buttons', 'Could not apply the new order — please retry.');
        }
    }

    public function removeButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $current = collect($this->buttonRows($page))
            ->reject(fn (array $row) => $row['id'] === $buttonId)
            ->values()
            ->all();

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

    /**
     * Mint a 30-minute signed draft link that renders the page
     * in-action on its own domain — saved but unpublished state,
     * no login needed, never tracked or indexed.
     */
    public function makeDraftLink(): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page || ! $page->domain) {
            return;
        }

        $previous = URL::to('/');
        URL::forceRootUrl('https://'.$page->domain->hostname);

        try {
            $expires = now()->addMinutes(30);
            $this->draftUrl = URL::temporarySignedRoute('bio.draft', $expires, ['page' => $page->id]);
            $this->draftExpires = $expires->format('H:i');
        } finally {
            URL::forceRootUrl($previous);
        }
    }

    private function ownedPage(string $pageId): ?BioPage
    {
        // Full domain model (not id+hostname): actions hand it to
        // BioService, whose usability checks read is_active/verified_at.
        return auth()->user()->bioPages()->with(['domain', 'buttons', 'children'])->find($pageId);
    }

    private function buttonRows(BioPage $page): array
    {
        return app(BioService::class)->buttonRows($page);
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

        // Sub-page link targets: same bio family, never self.
        $actionTargets = collect();
        if ($editing) {
            $rootId = $editing->parent_id ?? $editing->id;
            $actionTargets = auth()->user()->bioPages()
                ->where('is_removed', false)
                ->where(fn ($q) => $q->where('id', $rootId)->orWhere('parent_id', $rootId))
                ->where('id', '!=', $editing->id)
                ->orderBy('sort_order')
                ->get();
        }

        // Phone-mockup preview: a replica filled with the live form
        // state (title/bio/avatar/theme/accent) over the saved buttons,
        // so theme and copy changes preview instantly, unsaved.
        $previewPage = null;
        $previewButtons = collect();
        if ($editing) {
            $previewPage = $editing->replicate()->fill([
                'title' => $this->title !== '' ? $this->title : $editing->title,
                'bio' => $this->bio,
                'avatar_url' => $this->avatar_url,
                'theme' => $this->theme,
                'accent' => $this->accent,
            ]);
            $previewButtons = $editing->buttons()->orderBy('sort_order')->get()->filter->isLive()->values();
        }

        return view('livewire.bio.page-builder', compact('pages', 'domains', 'editing', 'previewPage', 'previewButtons', 'actionTargets'));
    }
}
