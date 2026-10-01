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

        $rows = [];
        foreach (array_values($buttons) as $i => $b) {
            $kind = $b['kind'] ?? 'link';
            if (! in_array($kind, BioButton::KINDS, true)) {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: unknown kind."]);
            }

            $label = trim((string) ($b['label'] ?? ($kind === 'divider' ? '—' : '')));
            if ($kind !== 'divider' && $label === '') {
                throw ValidationException::withMessages(['buttons' => "Row {$i}: label is required."]);
            }

            $url = isset($b['destination_url']) && trim((string) $b['destination_url']) !== '' ? trim((string) $b['destination_url']) : null;
            if (in_array($kind, ['link', 'social'], true)) {
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

            if (! empty($b['thumbnail_url'])) {
                $this->unsafeUrls->rejectIfUnsafe(trim((string) $b['thumbnail_url']));
            }

            $rows[] = [
                'label' => mb_substr($label, 0, 60),
                'sublabel' => isset($b['sublabel']) && trim((string) $b['sublabel']) !== '' ? mb_substr(trim((string) $b['sublabel']), 0, 120) : null,
                'kind' => $kind,
                'destination_url' => $url,
                'icon' => $icon,
                'thumbnail_url' => ! empty($b['thumbnail_url']) ? trim((string) $b['thumbnail_url']) : null,
                'sort_order' => isset($b['sort_order']) ? max(0, min(255, (int) $b['sort_order'])) : $i,
                'is_active' => array_key_exists('is_active', $b) ? (bool) $b['is_active'] : true,
                'starts_at' => $b['starts_at'] ?? null,
                'ends_at' => $b['ends_at'] ?? null,
            ];
        }

        \DB::transaction(function () use ($page, $rows) {
            $page->buttons()->delete();
            foreach ($rows as $row) {
                $page->buttons()->create($row);
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
