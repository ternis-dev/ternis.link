<?php

namespace App\Livewire\Bio;

use App\Models\BioPage;
use App\Services\BioService;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Visual page-builder: phone-mockup preview beside the editor,
 * HTML5 drag-and-drop button ordering, per-page settings, sub-page
 * tabs, and signed draft links. Bound to one page family (root);
 * sub-pages switch the edited page via tabs.
 */
class VisualBuilder extends Component
{
    public BioPage $family;

    public ?string $editingPageId = null;

    public string $title = '';

    public ?string $bio = null;

    public ?string $avatar_url = null;

    public string $theme = 'minimal';

    public string $locale = 'en';

    public ?string $accent = null;

    public ?string $theme_color = null;

    public ?string $published_at = null;

    public string $newLabel = '';

    public ?string $newSublabel = null;

    public ?string $newUrl = '';

    public string $newKind = 'link';

    public string $newAction = 'url';

    public ?string $newTargetPage = null;

    public ?string $newModalTitle = null;

    public ?string $newModalBody = null;

    public ?string $draftUrl = null;

    public ?string $draftExpires = null;

    public function mount(BioPage $page): void
    {
        $page = auth()->user()->bioPages()->with('domain')->find($page->id);

        if (! $page) {
            abort(404);
        }

        $this->family = $page->parent_id === null ? $page : $page->parent;
        $this->edit($page->id);
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
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function edit(string $pageId): void
    {
        $page = $this->familyPage($pageId);

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
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->draftUrl = null;
        $this->draftExpires = null;
        $this->resetValidation();
    }

    public function save(BioService $bio): void
    {
        $page = $this->editing();

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
            'published_at' => $this->published_at ? new \DateTime($this->published_at) : null,
        ]);
        $bio->forgetCaches($page->fresh());
    }

    public function addButton(BioService $bio): void
    {
        $page = $this->editing();

        if (! $page) {
            return;
        }

        $this->validate([
            'newLabel' => ['required_unless:newKind,divider', 'string', 'max:60'],
            'newSublabel' => ['nullable', 'string', 'max:120'],
            'newUrl' => ['nullable', 'url', 'max:2048'],
            'newKind' => ['required', 'in:link,header,divider,social'],
            'newAction' => ['required', 'in:url,subpage,modal'],
            'newTargetPage' => ['required_if:newAction,subpage', 'nullable', 'string'],
            'newModalTitle' => ['required_if:newAction,modal', 'nullable', 'string', 'max:80'],
            'newModalBody' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($this->newKind, ['link', 'social'], true)
            && $this->newAction === 'url'
            && trim((string) $this->newUrl) === '') {
            $this->addError('newUrl', 'A URL is required for link buttons.');

            return;
        }

        $current = $bio->buttonRows($page);
        $current[] = [
            'label' => $this->newLabel !== '' ? $this->newLabel : '—',
            'sublabel' => $this->newSublabel ?: null,
            'kind' => $this->newKind,
            'action' => $this->newAction,
            'destination_url' => trim((string) $this->newUrl) !== '' ? trim((string) $this->newUrl) : null,
            'target_page_id' => $this->newTargetPage ?: null,
            'modal_title' => $this->newModalTitle ?: null,
            'modal_body' => $this->newModalBody ?: null,
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

        $this->reset(['newLabel', 'newSublabel', 'newUrl', 'newTargetPage', 'newModalTitle', 'newModalBody']);
        $this->newKind = 'link';
        $this->newAction = 'url';
    }

    public function toggleButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editing();

        if (! $page) {
            return;
        }

        $rows = collect($bio->buttonRows($page))->map(
            fn (array $row) => $row['id'] === $buttonId ? [...$row, 'is_active' => ! $row['is_active']] : $row
        )->all();

        $bio->syncButtons($page, $rows, auth()->user());
    }

    public function removeButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editing();

        if (! $page) {
            return;
        }

        $rows = collect($bio->buttonRows($page))
            ->reject(fn (array $row) => $row['id'] === $buttonId)
            ->values()
            ->all();

        $bio->syncButtons($page, $rows, auth()->user());
    }

    public function reorder(BioService $bio, array $orderedIds): void
    {
        $page = $this->editing();

        if (! $page) {
            return;
        }

        try {
            $bio->reorderButtons($page, $orderedIds, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('buttons', 'Could not apply the new order — please retry.');
        }
    }

    public function move(BioService $bio, string $buttonId, string $direction): void
    {
        $page = $this->editing();

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
        $this->reorder($bio, $ids);
    }

    public function makeDraftLink(): void
    {
        $page = $this->editing();

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

    private function editing(): ?BioPage
    {
        if ($this->editingPageId === null) {
            return null;
        }

        return $this->familyPage($this->editingPageId);
    }

    private function familyPage(string $pageId): ?BioPage
    {
        $rootId = $this->family->id;

        return auth()->user()->bioPages()
            ->with(['domain', 'buttons', 'children'])
            ->where(fn ($q) => $q->where('id', $rootId)->orWhere('parent_id', $rootId))
            ->find($pageId);
    }

    public function render(BioService $bio)
    {
        $editing = $this->editing();
        $family = $this->family->fresh(['domain', 'children']);

        $previewPage = null;
        $previewButtons = collect();
        $previewSubs = collect();
        if ($editing) {
            $previewPage = $editing->replicate()->fill([
                'title' => $this->title !== '' ? $this->title : $editing->title,
                'bio' => $this->bio,
                'avatar_url' => $this->avatar_url,
                'theme' => $this->theme,
                'locale' => $this->locale,
                'accent' => $this->accent,
                'theme_color' => $this->theme_color,
            ]);
            $previewButtons = $editing->buttons()->orderBy('sort_order')->get()->filter->isLive()->values();
            $previewSubs = $family->children()->where('is_removed', false)->where('is_active', true)->orderBy('sort_order')->get();
        }

        $actionTargets = $editing
            ? auth()->user()->bioPages()
                ->where('is_removed', false)
                ->where(fn ($q) => $q->where('id', $family->id)->orWhere('parent_id', $family->id))
                ->where('id', '!=', $editing->id)
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('livewire.bio.visual-builder', compact('editing', 'family', 'previewPage', 'previewButtons', 'previewSubs', 'actionTargets'));
    }
}
