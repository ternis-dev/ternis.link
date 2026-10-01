<?php

namespace App\Services;

use App\Models\BioButton;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BioService
{
    public const MAX_ROOTS_PER_USER = 3;

    public const MAX_SUBS_PER_PAGE = 10;

    public const MAX_BUTTONS_PER_PAGE = 25;

    public const RESERVED_SLUGS = [
        'url', 'go', 'preview', 'qr', 'bio', 't', 'new', 'login', 'auth', 'api', 'v1',
        'robots.txt', 'sitemap.xml', 'healthz', 'dashboard', 'admin', 'settings',
    ];

    public function __construct(
        private JunkUrlDetector $junkUrls,
        private UnsafeUrlValidator $unsafeUrls,
        private SocialPreviewValidator $socialPreview,
    ) {}

    /**
     * @throws ValidationException
     */
    public function createPage(User $user, Domain $domain, array $data, ?BioPage $parent = null): BioPage
    {
        $this->ensureCanUseDomain($user, $domain);

        if ($parent === null) {
            $roots = BioPage::where('user_id', $user->id)->whereNull('parent_id')->where('is_removed', false)->count();
            if ($roots >= self::MAX_ROOTS_PER_USER) {
                throw ValidationException::withMessages(['domain_id' => 'At most '.self::MAX_ROOTS_PER_USER.' bio pages.']);
            }
            if (BioPage::where('domain_id', $domain->id)->whereNull('parent_id')->where('is_removed', false)->exists()) {
                throw ValidationException::withMessages(['domain_id' => 'This domain already hosts a bio page.']);
            }
        } else {
            if ($parent->user_id !== $user->id) {
                throw ValidationException::withMessages(['parent_id' => 'Unknown page.']);
            }
            if ($parent->children()->where('is_removed', false)->count() >= self::MAX_SUBS_PER_PAGE) {
                throw ValidationException::withMessages(['slug' => 'At most '.self::MAX_SUBS_PER_PAGE.' sub-pages.']);
            }
            $domain = $parent->domain;
        }

        $slug = $parent === null ? '-' : $this->cleanSlug((string) ($data['slug'] ?? ''));
        $this->assertSlugFree($domain, $parent, $slug);

        $theme = $data['theme'] ?? 'minimal';
        $theme = in_array($theme, BioPage::THEMES, true) ? $theme : 'minimal';

        $locale = strtolower(trim((string) ($data['locale'] ?? 'en')));
        $locale = in_array($locale, BioPage::LOCALES, true) ? $locale : 'en';

        $themeColor = isset($data['theme_color']) && preg_match('/^#[0-9a-f]{6}$/i', trim((string) $data['theme_color'])) ? strtolower(trim((string) $data['theme_color'])) : null;

        $og = $this->socialPreview->normalize($data['og_title'] ?? null, $data['og_description'] ?? null, $data['og_image_url'] ?? null);

        if (! empty($data['avatar_url'])) {
            $this->unsafeUrls->rejectIfUnsafe(trim((string) $data['avatar_url']));
        }

        return BioPage::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'parent_id' => $parent?->id,
            'slug' => $slug,
            'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 80) ?: 'Links',
            'bio' => isset($data['bio']) && trim((string) $data['bio']) !== '' ? mb_substr(trim((string) $data['bio']), 0, 280) : null,
            'avatar_url' => ! empty($data['avatar_url']) ? trim((string) $data['avatar_url']) : null,
            'theme' => $theme,
            'locale' => $locale,
            'theme_color' => $themeColor,
            'accent' => isset($data['accent']) && preg_match('/^#[0-9a-f]{6}$/i', trim((string) $data['accent'])) ? strtolower(trim((string) $data['accent'])) : null,
            'og_title' => $og['og_title'],
            'og_description' => $og['og_description'],
            'og_image_url' => $og['og_image_url'],
            'is_active' => $data['is_active'] ?? true,
            'published_at' => $data['published_at'] ?? null,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function syncButtons(BioPage $page, array $buttons, ?User $actor = null): void
    {
        if (count($buttons) > self::MAX_BUTTONS_PER_PAGE) {
            throw ValidationException::withMessages(['buttons' => 'At most '.self::MAX_BUTTONS_PER_PAGE.' buttons per page.']);
        }

        $existing = $page->buttons()->get()->keyBy('id');

        $rows = [];
        $seenIds = [];
        foreach (array_values($buttons) as $i => $b) {
            $id = $b['id'] ?? null;
            if ($id !== null) {
                if (! $existing->has($id)) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: unknown button."]);
                }
                if (in_array($id, $seenIds, true)) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: duplicate button."]);
                }
                $seenIds[] = $id;
            }
            $kind = $b['kind'] ?? 'link';
            if (! in_array($kind, BioButton::KINDS, true)) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: unknown kind."]);
            }

            $label = trim((string) ($b['label'] ?? ($kind === 'divider' ? '—' : '')));
            if ($kind !== 'divider' && $label === '') {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: label is required."]);
            }

            $url = isset($b['destination_url']) && trim((string) $b['destination_url']) !== '' ? trim((string) $b['destination_url']) : null;

            // Button action: plain URL (default), sub-page link, or modal pop-up.
            $action = strtolower(trim((string) ($b['action'] ?? 'url')));
            if (! in_array($kind, ['link', 'social'], true)) {
                $action = 'url';
            } elseif (! in_array($action, BioButton::ACTIONS, true)) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: action must be url, subpage or modal."]);
            }

            if ($action === 'url' && in_array($kind, ['link', 'social'], true)) {
                if ($url === null) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: destination_url is required."]);
                }
                $actingUser = $actor ?? $page->user;
                if ($actingUser === null || ! $actingUser->isAdmin()) {
                    $this->unsafeUrls->rejectIfUnsafe($url);
                }
                $this->junkUrls->rejectIfJunk($url);
            }

            $icon = $b['icon'] ?? null;
            $icon = $icon !== null && trim((string) $icon) !== '' ? strtolower(trim((string) $icon)) : null;
            if ($icon !== null && ! in_array($icon, BioButton::ICONS, true)) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: unknown icon."]);
            }

            $targetPageId = null;
            $modalTitle = null;
            $modalBody = null;
            $modalImage = null;

            if ($action === 'subpage') {
                $targetPageId = $b['target_page_id'] ?? null;
                $target = $targetPageId !== null ? BioPage::find($targetPageId) : null;
                if (! $target || $target->is_removed || $target->user_id !== $page->user_id) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: pick one of your pages."]);
                }
                $rootA = $page->parent_id ?? $page->id;
                $rootB = $target->parent_id ?? $target->id;
                if ($rootA !== $rootB || $target->id === $page->id) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: sub-page links stay inside the same bio page family."]);
                }
                $url = null;
            }

            if ($action === 'modal') {
                $modalTitle = isset($b['modal_title']) ? trim((string) $b['modal_title']) : '';
                if ($modalTitle === '') {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: modal needs a title."]);
                }
                $modalTitle = mb_substr($modalTitle, 0, 80);
                $modalBody = isset($b['modal_body']) && trim((string) $b['modal_body']) !== '' ? mb_substr(trim((string) $b['modal_body']), 0, 1000) : null;
                if (! empty($b['modal_image_url'])) {
                    $modalImage = trim((string) $b['modal_image_url']);
                    $this->unsafeUrls->rejectIfUnsafe($modalImage);
                }
                $url = null;
            }

            if (! empty($b['thumbnail_url'])) {
                $this->unsafeUrls->rejectIfUnsafe(trim((string) $b['thumbnail_url']));
            }

            $rows[] = [
                'id' => $id,
                'label' => mb_substr($label, 0, 60),
                'sublabel' => isset($b['sublabel']) && trim((string) $b['sublabel']) !== '' ? mb_substr(trim((string) $b['sublabel']), 0, 120) : null,
                'kind' => $kind,
                'action' => $action,
                'destination_url' => $url,
                'target_page_id' => $targetPageId,
                'modal_title' => $modalTitle,
                'modal_body' => $modalBody,
                'modal_image_url' => $modalImage,
                'icon' => $icon,
                'thumbnail_url' => ! empty($b['thumbnail_url']) ? trim((string) $b['thumbnail_url']) : null,
                'sort_order' => isset($b['sort_order']) ? max(0, min(255, (int) $b['sort_order'])) : $i,
                'is_active' => array_key_exists('is_active', $b) ? (bool) $b['is_active'] : true,
                'starts_at' => $b['starts_at'] ?? null,
                'ends_at' => $b['ends_at'] ?? null,
            ];
        }

        \DB::transaction(function () use ($page, $rows, $seenIds) {
            // Identity-preserving replace: update rows that carry a
            // known id (tap counts + event links survive), create the
            // rest, drop anything missing from the payload.
            $page->buttons()->whereNotIn('id', $seenIds)->delete();
            foreach ($rows as $row) {
                $id = $row['id'];
                unset($row['id']);

                if ($id !== null) {
                    $page->buttons()->where('id', $id)->update($row);
                } else {
                    $page->buttons()->create($row);
                }
            }
            $this->forgetCaches($page);
        });
    }

    public function forgetCaches(BioPage $page): void
    {
        Cache::forget(BioPage::cacheKeyPage($page->id));
        $root = $page->parent_id === null ? $page : $page->parent;
        if ($root && $root->domain_id) {
            Cache::forget(BioPage::cacheKeyRoot($root->domain_id));
        }
        if ($page->domain_id) {
            Cache::forget(BioPage::cacheKeyRoot($page->domain_id));
        }
    }

    /**
     * Full button rows for sync round-trips (add/remove/toggle/move/
     * reorder). Carries every service-managed field so unrelated
     * attributes (schedules, icons, actions, thumbnails) survive any
     * single-button op.
     */
    public function buttonRows(BioPage $page): array
    {
        return $page->buttons()->orderBy('sort_order')->get()->map(fn (BioButton $b) => [
            'id' => $b->id,
            'label' => $b->label,
            'sublabel' => $b->sublabel,
            'kind' => $b->kind,
            'action' => $b->action,
            'destination_url' => $b->destination_url,
            'target_page_id' => $b->target_page_id,
            'modal_title' => $b->modal_title,
            'modal_body' => $b->modal_body,
            'modal_image_url' => $b->modal_image_url,
            'icon' => $b->icon,
            'thumbnail_url' => $b->thumbnail_url,
            'sort_order' => $b->sort_order,
            'is_active' => $b->is_active,
            'starts_at' => $b->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $b->ends_at?->format('Y-m-d\TH:i'),
        ])->all();
    }

    /**
     * Reorder buttons to the given id sequence (drag-and-drop).
     * Unknown or duplicate ids are rejected; omitted buttons stay
     * untouched at the end in their current order.
     *
     * @param  list<string>  $orderedIds
     *
     * @throws ValidationException
     */
    public function reorderButtons(BioPage $page, array $orderedIds, ?User $actor = null): void
    {
        $rows = collect($this->buttonRows($page))->keyBy('id');

        $seen = [];
        foreach ($orderedIds as $id) {
            if (! is_string($id) || ! $rows->has($id)) {
                throw ValidationException::withMessages(['buttons' => 'Unknown button in order.']);
            }
            if (in_array($id, $seen, true)) {
                throw ValidationException::withMessages(['buttons' => 'Duplicate button in order.']);
            }
            $seen[] = $id;
        }

        $ordered = [];
        foreach ($orderedIds as $id) {
            $ordered[] = $rows->get($id);
        }
        foreach ($rows as $id => $row) {
            if (! in_array($id, $seen, true)) {
                $ordered[] = $row;
            }
        }

        foreach ($ordered as $i => &$row) {
            $row['sort_order'] = $i;
        }

        $this->syncButtons($page, $ordered, $actor);
    }

    private function ensureCanUseDomain(User $user, Domain $domain): void
    {
        if ($domain->user_id !== null && $domain->user_id !== $user->id && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['domain_id' => 'You do not own this domain.']);
        }

        if (! $domain->isUsableForLinks()) {
            throw ValidationException::withMessages(['domain_id' => 'Domain must be active and verified.']);
        }

        if (! $user->isAdmin() && ! (bool) $user->plan?->allowsCustomSubdomain() && $domain->user_id !== null) {
            // Custom user domains require an eligible plan; system domains never host bio in v1.
            throw ValidationException::withMessages(['domain_id' => 'Your plan does not include bio pages on custom domains.']);
        }

        if ($domain->isSystemDomain()) {
            throw ValidationException::withMessages(['domain_id' => 'Bio pages need your own verified domain.']);
        }
    }

    private function cleanSlug(string $slug): string
    {
        return strtolower(trim($slug));
    }

    /**
     * @throws ValidationException
     */
    private function assertSlugFree(Domain $domain, ?BioPage $parent, string $slug): void
    {
        if ($parent === null) {
            return;
        }

        if (! preg_match('/^[a-z0-9-]{1,64}$/', $slug)) {
            throw ValidationException::withMessages(['slug' => 'Sub-page slug: lowercase letters, numbers and dashes (1–64).']);
        }

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            throw ValidationException::withMessages(['slug' => 'This slug is reserved.']);
        }

        if (BioPage::where('parent_id', $parent->id)->where('slug', $slug)->where('is_removed', false)->exists()) {
            throw ValidationException::withMessages(['slug' => 'A sub-page with this slug already exists.']);
        }

        // First-write-wins against short links on the same domain.
        if (Link::where('domain_id', $domain->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'Slug taken by a short link on this domain.']);
        }
    }
}
