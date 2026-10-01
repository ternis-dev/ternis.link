<?php

namespace App\Services;

use App\Models\BioButton;
use App\Models\BioPage;
use App\Models\Domain;
use App\Models\Link;
use App\Models\User;
use App\Support\BioVideo;
use Illuminate\Support\Facades\Hash;
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

        $buttonStyle = $data['button_style'] ?? 'filled';
        $buttonStyle = in_array($buttonStyle, BioPage::BUTTON_STYLES, true) ? $buttonStyle : 'filled';

        $layout = $data['layout'] ?? 'list';
        $layout = in_array($layout, BioPage::LAYOUTS, true) ? $layout : 'list';

        // Removing the "Powered by" line is a premium perk (same gate
        // as custom domains); everyone else keeps the attribution.
        $hideBranding = ! empty($data['hide_branding']) && self::canHideBranding($user);

        $passwordHint = isset($data['password_hint']) && trim((string) $data['password_hint']) !== '' ? mb_substr(trim((string) $data['password_hint']), 0, 120) : null;

        $goneUrl = isset($data['gone_url']) && trim((string) $data['gone_url']) !== '' ? trim((string) $data['gone_url']) : null;
        if ($goneUrl !== null) {
            $this->unsafeUrls->rejectIfUnsafe($goneUrl);
            $this->junkUrls->rejectIfJunk($goneUrl);
        }

        $og = $this->socialPreview->normalize($data['og_title'] ?? null, $data['og_description'] ?? null, $data['og_image_url'] ?? null);

        if (! empty($data['avatar_url'])) {
            $this->unsafeUrls->rejectIfUnsafe(trim((string) $data['avatar_url']));
        }

        $coverUrl = null;
        if (! empty($data['cover_url'])) {
            $coverUrl = trim((string) $data['cover_url']);
            $this->unsafeUrls->rejectIfUnsafe($coverUrl);
        }

        $footerText = isset($data['footer_text']) && trim((string) $data['footer_text']) !== '' ? mb_substr(trim((string) $data['footer_text']), 0, 140) : null;

        $announcementText = isset($data['announcement_text']) && trim((string) $data['announcement_text']) !== '' ? mb_substr(trim((string) $data['announcement_text']), 0, 140) : null;
        $announcementUrl = isset($data['announcement_url']) && trim((string) $data['announcement_url']) !== '' ? trim((string) $data['announcement_url']) : null;
        if ($announcementUrl !== null) {
            $this->unsafeUrls->rejectIfUnsafe($announcementUrl);
            $this->junkUrls->rejectIfJunk($announcementUrl);
        }

        return BioPage::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'parent_id' => $parent?->id,
            'slug' => $slug,
            'title' => mb_substr(trim((string) ($data['title'] ?? '')), 0, 80) ?: 'Links',
            'bio' => isset($data['bio']) && trim((string) $data['bio']) !== '' ? mb_substr(trim((string) $data['bio']), 0, 280) : null,
            'avatar_url' => ! empty($data['avatar_url']) ? trim((string) $data['avatar_url']) : null,
            'cover_url' => $coverUrl,
            'footer_text' => $footerText,
            'announcement_text' => $announcementText,
            'announcement_url' => $announcementUrl,
            'theme' => $theme,
            'locale' => $locale,
            'theme_color' => $themeColor,
            'button_style' => $buttonStyle,
            'layout' => $layout,
            'hide_branding' => $hideBranding,
            'password_hint' => $passwordHint,
            'gone_url' => $goneUrl,
            'show_stats' => ! empty($data['show_stats']),
            'accent' => isset($data['accent']) && preg_match('/^#[0-9a-f]{6}$/i', trim((string) $data['accent'])) ? strtolower(trim((string) $data['accent'])) : null,
            'og_title' => $og['og_title'],
            'og_description' => $og['og_description'],
            'og_image_url' => $og['og_image_url'],
            'is_active' => $data['is_active'] ?? true,
            'published_at' => $data['published_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
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

            $contactEmail = isset($b['contact_email']) ? trim((string) $b['contact_email']) : '';
            $contactPhone = isset($b['contact_phone']) ? trim((string) $b['contact_phone']) : '';

            if ($kind === 'contact') {
                if ($contactEmail !== '' && ! filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: contact email is invalid."]);
                }
                if ($contactPhone !== '' && ! preg_match('/^[+0-9][0-9 .()\/-]{3,38}$/', $contactPhone)) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: contact phone is invalid."]);
                }
                if ($contactEmail === '' && $contactPhone === '') {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: contact needs an email or a phone number."]);
                }
            }

            if ($kind === 'image' && empty($b['thumbnail_url'])) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: image blocks need a thumbnail URL."]);
            }

            if ($kind === 'coupon' && trim((string) ($b['sublabel'] ?? '')) === '') {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: coupon blocks need the code in the sublabel."]);
            }

            $badge = isset($b['badge']) ? trim((string) $b['badge']) : '';
            if (mb_strlen($badge) > 12) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: badges are 12 characters max."]);
            }

            $eventAt = $b['event_at'] ?? null;
            if ($kind === 'countdown') {
                if ($eventAt === null || $eventAt === '') {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: countdowns need a date and time."]);
                }
                try {
                    $eventAt = new \DateTimeImmutable((string) $eventAt);
                } catch (\Throwable) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: countdown date is invalid."]);
                }
                $eventAt = $eventAt->format('Y-m-d H:i:s');
            } elseif ($eventAt !== null && $eventAt !== '') {
                // Stray dates on other kinds are dropped, not stored.
                $eventAt = null;
            } else {
                $eventAt = null;
            }

            $url = isset($b['destination_url']) && trim((string) $b['destination_url']) !== '' ? trim((string) $b['destination_url']) : null;

            // Button action: plain URL (default), sub-page link, or modal pop-up.
            $action = strtolower(trim((string) ($b['action'] ?? 'url')));
            if (! in_array($kind, ['link', 'social'], true)) {
                $action = 'url';
            } elseif (! in_array($action, BioButton::ACTIONS, true)) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: action must be url, subpage or modal."]);
            }

            if ($action === 'url' && in_array($kind, ['link', 'social', 'video'], true)) {
                if ($url === null) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: destination_url is required."]);
                }
                $actingUser = $actor ?? $page->user;
                if ($actingUser === null || ! $actingUser->isAdmin()) {
                    $this->unsafeUrls->rejectIfUnsafe($url);
                }
                $this->junkUrls->rejectIfJunk($url);

                if ($kind === 'video' && ! BioVideo::isVideoUrl($url)) {
                    throw ValidationException::withMessages(['buttons' => "Row {$i}: video links must be YouTube or Vimeo URLs."]);
                }
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
                'contact_email' => $contactEmail !== '' ? mb_substr($contactEmail, 0, 255) : null,
                'contact_phone' => $contactPhone !== '' ? mb_substr($contactPhone, 0, 40) : null,
                'icon' => $icon,
                'thumbnail_url' => ! empty($b['thumbnail_url']) ? trim((string) $b['thumbnail_url']) : null,
                'sort_order' => isset($b['sort_order']) ? max(0, min(255, (int) $b['sort_order'])) : $i,
                'is_active' => array_key_exists('is_active', $b) ? (bool) $b['is_active'] : true,
                'open_new' => array_key_exists('open_new', $b) ? (bool) $b['open_new'] : false,
                'badge' => $badge !== '' ? mb_substr($badge, 0, 12) : null,
                'event_at' => $eventAt,
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
        BioPage::flushCaches($page->fresh() ?? $page);
    }

    /**
     * Set (or rotate) a page password. Minimum 8 chars; stored as a
     * bcrypt hash, never in plain text.
     *
     * @throws ValidationException
     */
    public function setPassword(BioPage $page, string $password): void
    {
        if (mb_strlen($password) < 8) {
            throw ValidationException::withMessages(['password' => 'The password must be at least 8 characters.']);
        }

        $page->update(['password_hash' => Hash::make($password)]);
        $this->forgetCaches($page);
    }

    public function clearPassword(BioPage $page): void
    {
        $page->update(['password_hash' => null]);
        $this->forgetCaches($page);
    }

    public static function sessionKey(string $pageId): string
    {
        return "bio_unlocked_{$pageId}";
    }

    public static function canHideBranding(User $user): bool
    {
        return $user->isAdmin() || (bool) $user->plan?->allowsCustomSubdomain();
    }

    /**
     * Build a vCard 3.0 payload for a contact button.
     */
    public static function vcard(BioButton $button): string
    {
        $lines = ['BEGIN:VCARD', 'VERSION:3.0'];
        $name = $button->modal_title ?: $button->label;
        $lines[] = 'FN:'.self::escapeVcard($name);
        $parts = preg_split('/\s+/', trim($name), 2);
        $lines[] = 'N:'.self::escapeVcard(($parts[1] ?? '').';'.($parts[0] ?? ''));
        if ($button->contact_email) {
            $lines[] = 'EMAIL:'.self::escapeVcard($button->contact_email);
        }
        if ($button->contact_phone) {
            $lines[] = 'TEL;TYPE=CELL:'.self::escapeVcard($button->contact_phone);
        }
        if ($button->modal_body) {
            $lines[] = 'NOTE:'.self::escapeVcard(mb_substr($button->modal_body, 0, 500));
        }
        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines)."\r\n";
    }

    private static function escapeVcard(string $value): string
    {
        return str_replace(['\\', "\r\n", "\n", ',', ';'], ['\\\\', '\\n', '\\n', '\\,', '\\;'], $value);
    }

    /**
     * Duplicate a page (fields + buttons, zeroed counters) onto a
     * target domain/root. Sub-page slugs auto-suffix when taken.
     *
     * @throws ValidationException
     */
    public function duplicatePage(User $actor, BioPage $page, Domain $domain, ?BioPage $parent = null): BioPage
    {
        if ($parent !== null && ($parent->parent_id !== null || $parent->user_id !== $actor->id)) {
            throw ValidationException::withMessages(['parent_id' => 'Unknown page.']);
        }

        $copy = $this->createPage($actor, $domain, [
            'slug' => $parent === null ? null : $this->freeSubSlug($parent, $page->slug),
            'title' => mb_substr($page->title.' (copy)', 0, 80),
            'bio' => $page->bio,
            'avatar_url' => $page->avatar_url,
            'cover_url' => $page->cover_url,
            'footer_text' => $page->footer_text,
            'theme' => $page->theme,
            'locale' => $page->locale,
            'accent' => $page->accent,
            'theme_color' => $page->theme_color,
            'button_style' => $page->button_style,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image_url' => $page->og_image_url,
            'is_active' => false,
        ], $parent);

        $rows = collect($this->buttonRows($page->fresh()))->map(function (array $row) use ($page) {
            unset($row['id']);

            // Sub-page links can't cross over: freeze them as plain
            // URLs to the original location. Modals copy verbatim.
            if (($row['action'] ?? 'url') === 'subpage') {
                $target = $row['target_page_id'] ? BioPage::find($row['target_page_id']) : null;
                $host = $page->domain?->hostname;
                if ($target && $host) {
                    $path = $target->parent_id === null ? '' : '/'.$target->slug;
                    $row['action'] = 'url';
                    $row['destination_url'] = "https://{$host}{$path}";
                } else {
                    $row['action'] = 'url';
                    $row['destination_url'] = "https://{$host}";
                }
                $row['target_page_id'] = null;
            }

            return $row;
        })->all();

        $this->syncButtons($copy, $rows, $actor);

        return $copy->fresh();
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
            'open_new' => $b->open_new,
            'badge' => $b->badge,
            'event_at' => $b->event_at?->format('Y-m-d\TH:i'),
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
     * First free `{slug}`, `{slug}-copy`, `{slug}-copy-2`, … under a parent.
     */
    private function freeSubSlug(BioPage $parent, string $slug): string
    {
        $base = $this->cleanSlug($slug) !== '' ? $this->cleanSlug($slug) : 'page';
        $candidate = $base;
        $n = 1;

        while (BioPage::where('parent_id', $parent->id)->where('slug', $candidate)->where('is_removed', false)->exists()
            || Link::where('domain_id', $parent->domain_id)->where('slug', $candidate)->exists()) {
            $n++;
            $candidate = "{$base}-copy".($n > 2 ? "-{$n}" : '');
        }

        return $candidate;
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
