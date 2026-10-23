@php
    $rp = \App\Support\PublicDashboard::routePrefixFor($theme ?? 'dashboard');
    $hasActiveFilters = $search !== '' || $tag !== '' || ($domainFilter ?? '') !== '' || ($statusFilter ?? 'all') !== 'all' || ($clicksFilter ?? 'all') !== 'all' || ($sortOption ?? 'newest') !== 'newest' || ($adminScope ?? 'mine') !== 'mine' || ($lockedApiKeyId === null && ($apiKeyFilter ?? '') !== '');
@endphp
<div wire:poll.30s.visible>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2.5">
            <x-ui.input
                name="table-search"
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $isAdmin && ($adminScope ?? '') === 'all' ? 'Search all platform links by slug, URL, tag, owner...' : 'Search by slug, URL, description or tag...' }}"
                autocomplete="off"
                class="max-w-md flex-1 min-w-[200px]"
            />

            {{-- Sort selection dropdown: most clicks, least clicks, newest, etc. --}}
            <x-ui.select name="table-sort" wire:model.live="sortOption" class="w-auto" aria-label="Sort links">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="most_clicks">Most clicks</option>
                <option value="least_clicks">Least clicks</option>
                <option value="slug_asc">Short Link (A–Z)</option>
                <option value="slug_desc">Short Link (Z–A)</option>
            </x-ui.select>

            {{-- Clicks filter: enables instant filtering to links with clicks/high traffic --}}
            <x-ui.select name="table-clicks-filter" wire:model.live="clicksFilter" class="w-auto" aria-label="Filter by clicks">
                <option value="all">All click traffic</option>
                <option value="has_clicks">With clicks (&gt; 0)</option>
                <option value="100_plus">100+ clicks (Top)</option>
                <option value="10_plus">10+ clicks</option>
                <option value="zero">0 clicks (Dormant)</option>
            </x-ui.select>

            {{-- Status filter --}}
            <x-ui.select name="table-status" wire:model.live="statusFilter" class="w-auto" aria-label="Filter by status">
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="disabled">Disabled</option>
                <option value="expired">Expired</option>
                <option value="password">Password protected</option>
            </x-ui.select>

            {{-- Domain filter across all dashboard themes --}}
            @if (!empty($availableDomains))
                <x-ui.select name="table-domain" wire:model.live="domainFilter" class="w-auto" aria-label="Filter by domain">
                    <option value="">All domains</option>
                    @foreach ($availableDomains as $hostname)
                        <option value="{{ $hostname }}">{{ $hostname }}</option>
                    @endforeach
                </x-ui.select>
            @endif

            {{-- Tag filter --}}
            @if ($availableTags !== [])
                <x-ui.select name="table-tag" wire:model.live="tag" class="w-auto" aria-label="Filter by tag">
                    <option value="">All tags</option>
                    @foreach ($availableTags as $availableTag)
                        <option value="{{ $availableTag }}">{{ $availableTag }}</option>
                    @endforeach
                </x-ui.select>
            @endif

            {{-- Origin / API Key filter --}}
            @if (! isset($lockedApiKeyId) || $lockedApiKeyId === null)
                @if (isset($apiKeys) && $apiKeys->isNotEmpty())
                    <x-ui.select name="table-api-key" wire:model.live="apiKeyFilter" class="w-auto" aria-label="Filter by API key">
                        <option value="">All origins</option>
                        <option value="none">Dashboard only</option>
                        @foreach ($apiKeys as $apiKey)
                            <option value="{{ $apiKey->id }}">{{ $apiKey->name }} ({{ $apiKey->key_prefix }}…){{ ($apiKeyLinkCounts[$apiKey->id] ?? 0) === 0 ? ' — no links here' : '' }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
                <label class="flex shrink-0 cursor-pointer items-center gap-1.5 text-xs whitespace-nowrap text-neutral-500 dark:text-neutral-400" title="Also show links made with API keys hidden from the dashboard">
                    <x-ui.checkbox wire:model.live="includeHidden" />
                    Include hidden
                </label>
            @endif

            {{-- Admin Scope Switcher: Switch between "My links" and "All links (platform-wide)" --}}
            @if ($isAdmin)
                <x-ui.select name="table-admin-scope" wire:model.live="adminScope" class="w-auto font-semibold border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200" aria-label="Admin Scope">
                    <option value="mine">Scope: My links</option>
                    <option value="all">Scope: All links (System-wide)</option>
                </x-ui.select>
            @endif

            @if ($hasActiveFilters)
                <button type="button" wire:click="resetFilters" class="text-xs font-semibold text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-200 underline cursor-pointer shrink-0">
                    Reset
                </button>
            @endif
        </div>
        <div class="flex items-center gap-2">
            @include('livewire.partials.column-customizer')
            <x-ui.button href="{{ route($rp.'links.create') }}" variant="primary">+ Create Link</x-ui.button>
        </div>
    </div>

    <x-ui.table>
        <thead>
            <tr>
                <th class="w-8">
                    <x-ui.checkbox
                        aria-label="Select all on this page"
                        @checked(count($selected) > 0 && count(array_intersect($selected, $pageIds)) === count($pageIds) && count($pageIds) > 0)
                        wire:change="toggleSelectAll($event.target.checked)"
                    />
                </th>
                @foreach ($visibleColumns as $column)
                    @if ($column === 'slug')
                        <th aria-sort="{{ $sortBy === 'slug' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('slug')" class="inline-flex cursor-pointer items-center gap-1 uppercase hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                                Short Link
                                @if ($sortBy === 'slug') <span aria-hidden="true">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                            </button>
                        </th>
                    @elseif ($column === 'destination')
                        <th>Destination URL</th>
                    @elseif ($column === 'domain')
                        <th>Domain</th>
                    @elseif ($column === 'owner')
                        <th>Owner</th>
                    @elseif ($column === 'clicks')
                        <th aria-sort="{{ $sortBy === 'click_count' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('click_count')" class="inline-flex cursor-pointer items-center gap-1 uppercase hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                                Clicks
                                @if ($sortBy === 'click_count') <span aria-hidden="true">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                            </button>
                        </th>
                    @elseif ($column === 'status')
                        <th>Status</th>
                    @elseif ($column === 'created')
                        <th aria-sort="{{ $sortBy === 'created_at' ? ($sortDir === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <button type="button" wire:click="sort('created_at')" class="inline-flex cursor-pointer items-center gap-1 uppercase hover:underline focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current">
                                Created
                                @if ($sortBy === 'created_at') <span aria-hidden="true">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                            </button>
                        </th>
                    @endif
                @endforeach
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($links as $link)
                @php
                    $rowQuery = isset($fromApiKey) && $fromApiKey ? ['from_api_key' => $fromApiKey] : [];
                    $isOwnLink = auth()->id() === $link->user_id;
                    $linkShowUrl = $isOwnLink ? route($rp.'links.show', array_merge([$link->id], $rowQuery)) : \App\Support\DomainUrls::admin('/links');
                    $linkEditUrl = $isOwnLink ? route($rp.'links.edit', array_merge([$link->id], $rowQuery)) : null;
                @endphp
                <tr>
                    <td>
                        <input type="checkbox" wire:model="selected" value="{{ $link->id }}" aria-label="Select this link" class="h-4 w-4 rounded accent-neutral-900 dark:accent-white">
                    </td>
                    @foreach ($visibleColumns as $column)
                        @if ($column === 'slug')
                            <td>
                                <a href="{{ $linkShowUrl }}" class="font-semibold underline-offset-2 hover:underline">
                                    {{ $link->slug }}
                                </a>
                                <button type="button" data-copy="{{ $link->short_url }}" title="Copy short link" aria-label="Copy short link {{ $link->slug }} to clipboard" class="ml-1 inline-flex cursor-pointer items-center rounded-full border border-neutral-300 px-1.5 py-px text-[11px] font-medium text-neutral-600 transition-colors hover:border-neutral-900 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-white dark:hover:text-white">Copy</button>
                                @if ($link->apiKey)
                                    <p class="mt-0.5 text-[11px] text-neutral-400 dark:text-neutral-600" title="Created with API key {{ $link->apiKey->name }}">via {{ $link->apiKey->name }}</p>
                                @endif
                            </td>
                        @elseif ($column === 'destination')
                            <td class="max-w-[300px]">
                                <a href="{{ $link->destination_url }}" target="_blank" rel="noopener noreferrer" class="block truncate text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white">
                                    {{ $link->destination_url }}
                                </a>
                                @if ($link->description)
                                    <p class="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-500" title="{{ $link->description }}">{{ $link->description }}</p>
                                @endif
                                @if (! empty($link->tags))
                                    <p class="mt-1 flex flex-wrap gap-1">
                                        @foreach (array_slice($link->tags, 0, 3) as $tag)
                                            <button type="button" wire:click="$set('tag', @js($tag))" title="Filter by {{ $tag }}" class="inline-flex cursor-pointer items-center rounded-full border border-neutral-300 px-1.5 py-px text-[11px] font-medium text-neutral-600 transition-colors hover:border-neutral-900 hover:text-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:hover:border-white dark:hover:text-white">{{ $tag }}</button>
                                        @endforeach
                                        @if (count($link->tags) > 3)
                                            <span class="text-[11px] text-neutral-400 dark:text-neutral-600">+{{ count($link->tags) - 3 }}</span>
                                        @endif
                                    </p>
                                @endif
                            </td>
                        @elseif ($column === 'domain')
                            <td>
                                <code>{{ $link->domain->hostname ?? 'href.nz' }}</code>
                            </td>
                        @elseif ($column === 'owner')
                            <td class="tl-sensitive text-xs text-neutral-500 max-w-[180px] truncate" title="{{ $link->user?->email ?? 'Guest' }}">
                                {{ $link->user?->email ?? 'Guest' }}
                            </td>
                        @elseif ($column === 'clicks')
                            <td class="font-bold">{{ number_format($link->click_count) }}</td>
                        @elseif ($column === 'status')
                            <td>
                                @if ($link->is_active && !$link->isExpired())
                                    <x-ui.status state="active" />
                                @elseif ($link->isExpired())
                                    <x-ui.status state="expired" />
                                @else
                                    <x-ui.status state="disabled" />
                                @endif
                            </td>
                        @elseif ($column === 'created')
                            <td class="text-xs whitespace-nowrap text-neutral-500">
                                {{ $link->created_at->format('M d, Y') }}
                            </td>
                        @endif
                    @endforeach
                    <td>
                        <div class="flex gap-2">
                            <x-ui.button href="{{ $linkShowUrl }}" size="sm">{{ $isOwnLink ? 'Analytics' : 'Moderate' }}</x-ui.button>
                            @if ($linkEditUrl)
                                <x-ui.button href="{{ $linkEditUrl }}" size="sm">Edit</x-ui.button>
                            @endif
                            @if ($link->is_active)
                                <x-ui.button wire:click="deactivate('{{ $link->id }}')" wire:confirm="Deactivate this link?" wire:loading.attr="disabled" wire:target="deactivate" size="sm" variant="danger">Deactivate</x-ui.button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}">
                        @if ($hasActiveFilters)
                            <x-ui.empty-state>
                                No short links match your current search or filter.
                                <div class="mt-2">
                                    <button type="button" wire:click="resetFilters" class="cursor-pointer font-medium underline underline-offset-2 hover:text-neutral-900 dark:hover:text-neutral-100">Clear all filters</button>
                                </div>
                            </x-ui.empty-state>
                        @elseif (($hiddenByKeyCount ?? 0) > 0)
                            {{-- Links exist, but their API key keeps them off the dashboard. --}}
                            <x-ui.empty-state>
                                {{ number_format($hiddenByKeyCount) }} short link{{ $hiddenByKeyCount === 1 ? ' is' : 's are' }} hidden by your API-key settings.
                                <div class="mt-2">
                                    <button type="button" wire:click="$set('includeHidden', true)" class="cursor-pointer font-medium underline underline-offset-2 hover:text-neutral-900 dark:hover:text-neutral-100">Show them anyway</button>
                                </div>
                            </x-ui.empty-state>
                        @else
                            <x-ui.empty-state>No short links found. <a href="{{ route($rp.'links.create') }}" class="underline underline-offset-2">Create your first short link!</a></x-ui.empty-state>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-ui.table>

    <div class="mt-6">
        {{ $links->links() }}
    </div>

    @if (count($selected) > 0)
        <div class="sticky bottom-4 mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3 shadow-lg dark:border-neutral-700 dark:bg-neutral-900">
            <span class="text-sm font-medium">{{ count($selected) }} selected</span>
            <x-ui.button wire:click="bulkSetActive(true)" wire:loading.attr="disabled" wire:target="bulkSetActive" size="sm" variant="secondary">Activate</x-ui.button>
            <x-ui.button wire:click="bulkSetActive(false)" wire:loading.attr="disabled" wire:target="bulkSetActive" size="sm" variant="danger" wire:confirm="Deactivate the selected links?">Deactivate</x-ui.button>
            <button type="button" wire:click="$set('selected', [])" class="cursor-pointer text-xs text-neutral-500 hover:underline">Clear</button>
            @if ($bulkNotice)<span class="text-xs text-neutral-500">{{ $bulkNotice }}</span>@endif
        </div>
    @elseif ($bulkNotice)
        <p class="mt-4 text-xs text-neutral-500">{{ $bulkNotice }}</p>
    @endif
</div>
