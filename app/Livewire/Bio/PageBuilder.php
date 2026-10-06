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

    public ?string $template = null;

    public string $title = '';

    public ?string $bio = null;

    public ?string $avatar_url = null;

    public ?string $cover_url = null;

    public ?string $footer_text = null;

    public ?string $announcement_text = null;

    public ?string $announcement_url = null;

    public string $theme = 'minimal';

    public string $locale = 'en';

    public ?string $accent = null;

    public ?string $theme_color = null;

    public string $button_style = 'filled';

    public string $layout = 'list';

    public bool $hide_branding = false;

    public ?string $password_hint = null;

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

    public ?string $newContactEmail = null;

    public ?string $newContactPhone = null;

    public bool $newOpenNew = false;

    public ?string $newBadge = null;

    public ?string $newEventAt = null;

    public ?string $editingButtonId = null;

    public string $editLabel = '';

    public ?string $editSublabel = null;

    public ?string $editUrl = null;

    public ?string $editThumbnail = null;

    public ?string $editIcon = null;

    public bool $editOpenNew = false;

    public ?string $editBadge = null;

    public ?string $editStartsAt = null;

    public ?string $editEndsAt = null;

    public string $newAction = 'url';

    public ?string $newTargetPage = null;

    public ?string $newModalTitle = null;

    public ?string $newModalBody = null;

    public ?string $newStartsAt = null;

    public ?string $newEndsAt = null;

    public ?string $published_at = null;

    public ?string $expires_at = null;

    public ?string $gone_url = null;

    public bool $show_stats = false;

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
            'cover_url' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'footer_text' => ['nullable', 'string', 'max:140'],
            'announcement_text' => ['nullable', 'string', 'max:140'],
            'announcement_url' => ['nullable', 'url', 'max:2048'],
            'theme' => ['required', 'in:minimal,dark,paper,auto'],
            'locale' => ['required', 'in:en,de,fr,es,it'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'button_style' => ['required', 'in:filled,outline,soft'],
            'layout' => ['required', 'in:list,grid'],
            'hide_branding' => ['nullable', 'boolean'],
            'password_hint' => ['nullable', 'string', 'max:120'],
            'page_password' => ['nullable', 'string', 'min:8', 'max:72'],
            'slug' => ['nullable', 'string', 'max:64'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'gone_url' => ['nullable', 'url', 'max:2048'],
            'show_stats' => ['nullable', 'boolean'],
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
        $this->cover_url = $page->cover_url;
        $this->footer_text = $page->footer_text;
        $this->announcement_text = $page->announcement_text;
        $this->announcement_url = $page->announcement_url;
        $this->theme = $page->theme;
        $this->locale = $page->locale ?? 'en';
        $this->accent = $page->accent;
        $this->theme_color = $page->theme_color;
        $this->button_style = $page->button_style ?? 'filled';
        $this->layout = $page->layout ?? 'list';
        $this->hide_branding = (bool) $page->hide_branding;
        $this->password_hint = $page->password_hint;
        $this->page_password = null;
        $this->published_at = $page->published_at?->format('Y-m-d\TH:i');
        $this->expires_at = $page->expires_at?->format('Y-m-d\TH:i');
        $this->gone_url = $page->gone_url;
        $this->show_stats = (bool) $page->show_stats;
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
            if ($this->template !== null && $this->template !== '') {
                $page = $bio->createFromTemplate(auth()->user(), $domain, $this->template, $this->title !== '' ? $this->title : null);
            } else {
                $page = $bio->createPage(auth()->user(), $domain, [
                    'title' => $this->title !== '' ? $this->title : 'Links',
                    'bio' => $this->bio,
                    'avatar_url' => $this->avatar_url,
                    'theme' => $this->theme,
                    'accent' => $this->accent,
                ]);
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError($field === 'domain_id' ? 'domain_id' : 'title', $message);
                }
            }

            return;
        }

        $this->reset(['title', 'bio', 'avatar_url', 'slug', 'parent_id', 'template']);
        $this->theme = 'minimal';
        $this->selectPage($page->id);

        $this->dispatch('notify', message: 'Bio page created.', type: 'success');
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

        $this->dispatch('notify', message: 'Sub-page created.', type: 'success');
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
            'cover_url' => $this->cover_url ?: null,
            'footer_text' => $this->footer_text ?: null,
            'announcement_text' => $this->announcement_text ?: null,
            'announcement_url' => $this->announcement_url ?: null,
            'theme' => $this->theme,
            'locale' => $this->locale,
            'accent' => $this->accent ?: null,
            'theme_color' => $this->theme_color ?: null,
            'button_style' => $this->button_style,
            'layout' => $this->layout,
            'hide_branding' => $this->hide_branding && BioService::canHideBranding(auth()->user()),
            'password_hint' => $this->password_hint ?: null,
            'published_at' => $this->published_at ? new \DateTime($this->published_at) : null,
            'expires_at' => $this->expires_at ? new \DateTime($this->expires_at) : null,
            'gone_url' => $this->gone_url ?: null,
            'show_stats' => $this->show_stats,
        ]);

        if ($this->page_password !== null && trim($this->page_password) !== '') {
            $bio->setPassword($page->fresh(), $this->page_password);
        }

        $this->page_password = null;
        $bio->forgetCaches($page->fresh());

        $this->dispatch('notify', message: 'Page saved.', type: 'success');
    }

    public function removePassword(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $bio->clearPassword($page);

        $this->dispatch('notify', message: 'Page password removed.', type: 'info');
    }

    public function duplicateSub(BioService $bio, string $pageId): void
    {
        $page = $this->ownedPage($pageId);

        if (! $page || $page->parent_id === null) {
            return;
        }

        $parent = $page->parent;

        if (! $parent) {
            return;
        }

        try {
            $copy = $bio->duplicatePage(auth()->user(), $page, $parent->domain, $parent);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError('slug', $message);
                }
            }

            return;
        }

        $this->selectPage($copy->id);
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
            'newKind' => ['required', 'in:link,header,divider,social,contact,video,image,countdown,quote,coupon,rsvp'],
            'newContactEmail' => ['nullable', 'email', 'max:255'],
            'newContactPhone' => ['nullable', 'string', 'max:40'],
            'newOpenNew' => ['nullable', 'boolean'],
            'newBadge' => ['nullable', 'string', 'max:12'],
            'newEventAt' => ['nullable', 'date'],
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

        if ($this->newKind === 'contact'
            && trim((string) $this->newContactEmail) === ''
            && trim((string) $this->newContactPhone) === '') {
            $this->addError('newContactEmail', 'A contact needs an email or a phone number.');

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
            'contact_email' => $this->newContactEmail ?: null,
            'contact_phone' => $this->newContactPhone ?: null,
            'open_new' => $this->newOpenNew,
            'badge' => $this->newBadge ?: null,
            'event_at' => $this->newEventAt ?: null,
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

        $this->reset(['newLabel', 'newSublabel', 'newUrl', 'newIcon', 'newThumbnail', 'newContactEmail', 'newContactPhone', 'newOpenNew', 'newBadge', 'newEventAt', 'newStartsAt', 'newEndsAt', 'newTargetPage', 'newModalTitle', 'newModalBody']);
        $this->newKind = 'link';
        $this->newAction = 'url';

        $this->dispatch('notify', message: 'Button added.', type: 'success');
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

        $this->dispatch('notify', message: 'Button toggled.', type: 'info');
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

    public ?string $quickAdd = null;

    /** @var list<array{id: string, label: string, url: string, ok: bool, status: ?int}> */
    public array $linkHealth = [];

    public bool $checkingLinks = false;

    /** @var list<string> */
    public array $importLinkIds = [];

    public ?string $confirmingSubDelete = null;

    /**
     * Bulk-add buttons from pasted lines: "Label | https://…" or a
     * bare URL (label falls back to the host). Invalid lines are
     * skipped and reported; valid ones append in order.
     */
    public function quickAddButtons(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', (string) $this->quickAdd);
        $current = $bio->buttonRows($page);
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (count($current) >= BioService::MAX_BUTTONS_PER_PAGE) {
                $skipped++;

                continue;
            }

            if (str_contains($line, '|')) {
                [$label, $url] = array_map('trim', explode('|', $line, 2));
            } else {
                $url = $line;
                $label = (string) parse_url($line, PHP_URL_HOST);
            }

            if ($label === '' || $url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                $skipped++;

                continue;
            }

            $current[] = [
                'label' => mb_substr($label, 0, 60),
                'kind' => 'link',
                'destination_url' => mb_substr($url, 0, 2048),
                'sort_order' => count($current),
                'is_active' => true,
            ];
        }

        if ($skipped > 0) {
            $this->addError('quickAdd', "{$skipped} line(s) skipped (invalid URL, blank label, or page full).");
        }

        try {
            $bio->syncButtons($page, $current, auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError('quickAdd', $message);
                }
            }

            return;
        }

        $this->quickAdd = null;
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

        if ($this->editingButtonId === $buttonId) {
            $this->cancelEditButton();
        }

        $this->dispatch('notify', message: 'Button removed.', type: 'info');
    }

    public function startEditButton(string $buttonId): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $button = $page->buttons()->find($buttonId);

        if (! $button) {
            return;
        }

        $this->editingButtonId = $button->id;
        $this->editLabel = $button->label;
        $this->editSublabel = $button->sublabel;
        $this->editUrl = $button->destination_url;
        $this->editThumbnail = $button->thumbnail_url;
        $this->editIcon = $button->icon;
        $this->editOpenNew = (bool) $button->open_new;
        $this->editBadge = $button->badge;
        $this->editStartsAt = $button->starts_at?->format('Y-m-d\TH:i');
        $this->editEndsAt = $button->ends_at?->format('Y-m-d\TH:i');
        $this->resetValidation();
    }

    public function cancelEditButton(): void
    {
        $this->editingButtonId = null;
        $this->reset(['editLabel', 'editSublabel', 'editUrl', 'editThumbnail', 'editIcon', 'editBadge', 'editStartsAt', 'editEndsAt']);
        $this->editOpenNew = false;
        $this->resetValidation();
    }

    public function updateButton(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page || $this->editingButtonId === null) {
            return;
        }

        $this->validate([
            'editLabel' => ['required', 'string', 'max:60'],
            'editSublabel' => ['nullable', 'string', 'max:120'],
            'editUrl' => ['nullable', 'url', 'max:2048'],
            'editThumbnail' => ['nullable', 'url', 'starts_with:https', 'max:2048'],
            'editIcon' => ['nullable', 'in:instagram,tiktok,x,youtube,github,globe,mail,link'],
            'editOpenNew' => ['nullable', 'boolean'],
            'editBadge' => ['nullable', 'string', 'max:12'],
            'editStartsAt' => ['nullable', 'date'],
            'editEndsAt' => ['nullable', 'date', 'after:editStartsAt'],
        ]);

        $rows = collect($this->buttonRows($page))->map(function (array $row) {
            if ($row['id'] !== $this->editingButtonId) {
                return $row;
            }

            return [...$row,
                'label' => $this->editLabel,
                'sublabel' => $this->editSublabel ?: null,
                'destination_url' => $this->editUrl ?: null,
                'thumbnail_url' => $this->editThumbnail ?: null,
                'icon' => $this->editIcon ?: null,
                'open_new' => $this->editOpenNew,
                'badge' => $this->editBadge ?: null,
                'starts_at' => $this->editStartsAt ?: null,
                'ends_at' => $this->editEndsAt ?: null,
            ];
        })->all();

        try {
            $bio->syncButtons($page, $rows, auth()->user());
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    $this->addError('editUrl', $message);
                }
            }

            return;
        }

        $this->cancelEditButton();

        $this->dispatch('notify', message: 'Button updated.', type: 'success');
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

        $this->dispatch('notify', message: 'Page deactivated.', type: 'info');
    }

    public function duplicateButton(BioService $bio, string $buttonId): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $rows = $bio->buttonRows($page);
        $index = collect($rows)->search(fn (array $row) => $row['id'] === $buttonId);

        if ($index === false) {
            return;
        }

        if (count($rows) >= BioService::MAX_BUTTONS_PER_PAGE) {
            $this->addError('buttons', 'At most '.BioService::MAX_BUTTONS_PER_PAGE.' buttons per page.');

            return;
        }

        $copy = $rows[$index];
        unset($copy['id']);
        $copy['label'] = mb_substr($copy['label'].' (copy)', 0, 60);
        array_splice($rows, $index + 1, 0, [$copy]);

        foreach ($rows as $i => &$row) {
            $row['sort_order'] = $i;
        }

        $bio->syncButtons($page, $rows, auth()->user());
    }

    public function importLinks(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page || $this->importLinkIds === []) {
            return;
        }

        $links = auth()->user()->links()->notRemoved()
            ->with('domain:id,hostname')
            ->whereIn('id', array_slice($this->importLinkIds, 0, BioService::MAX_BUTTONS_PER_PAGE))
            ->get();

        $current = $bio->buttonRows($page);

        foreach ($links as $link) {
            if (count($current) >= BioService::MAX_BUTTONS_PER_PAGE) {
                break;
            }

            $host = $link->domain?->hostname ?? config('domains.public_host', 'href.nz');
            $current[] = [
                'label' => $link->description !== null && trim($link->description) !== ''
                    ? mb_substr(trim($link->description), 0, 60)
                    : $link->slug,
                'kind' => 'link',
                'destination_url' => "https://{$host}/{$link->slug}",
                'sort_order' => count($current),
                'is_active' => true,
            ];
        }

        try {
            $bio->syncButtons($page, $current, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('importLinkIds', 'Some links could not be imported.');

            return;
        }

        $this->importLinkIds = [];
    }

    public function deleteSub(BioService $bio, string $pageId): void
    {
        $page = $this->ownedPage($pageId);

        if (! $page || $page->parent_id === null) {
            return;
        }

        if ($this->confirmingSubDelete !== $pageId) {
            $this->confirmingSubDelete = $pageId;

            return;
        }

        $page->update(['is_removed' => true]);
        $bio->forgetCaches($page);
        $this->confirmingSubDelete = null;

        if ($this->editingPageId === $pageId) {
            $this->editingPageId = $page->parent_id;
            $this->selectPage($page->parent_id);
        }

        $this->dispatch('notify', message: 'Sub-page deleted.', type: 'error');
    }

    public function checkLinks(BioService $bio): void
    {
        $page = $this->editingPageId ? $this->ownedPage($this->editingPageId) : null;

        if (! $page) {
            return;
        }

        $this->checkingLinks = true;

        try {
            $this->linkHealth = $bio->checkLinks($page);
        } finally {
            $this->checkingLinks = false;
        }

        $broken = collect($this->linkHealth ?? [])->where('ok', false)->count();

        $this->dispatch(
            'notify',
            message: $broken === 0 ? 'All button links are healthy.' : "{$broken} button link(s) need attention.",
            type: $broken === 0 ? 'success' : 'error',
        );
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
        $pages = auth()->user()->bioPages()->with(['domain:id,hostname', 'children'])->where('is_removed', false)->orderByDesc('created_at')->get();
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
