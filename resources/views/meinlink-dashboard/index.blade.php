@extends('meinlink-dashboard.layout')

@section('content')
    <div class="page-heading ml-page-header">
        <div class="ml-page-header-main">
            <p class="eyebrow ml-eyebrow">DEIN LINK-ÜBERBLICK</p>
            <h1 class="ml-page-title">Weniger lang.<br>Mehr Überblick.</h1>
            <p class="ml-page-desc">Erstelle, teile und verwalte deine meinlink.at-Links.</p>
        </div>
        <div class="ml-page-header-actions">
            <a class="button primary ml-btn ml-btn-primary" href="{{ route('meinlink-dashboard.create') }}">
                <x-meinlink::icon name="plus" size="16" />
                <span>+ Neuen Link erstellen</span>
            </a>
        </div>
    </div>

    {{-- Schnellkürzer-Leiste direkt auf der Startseite --}}
    <section class="ml-quick-shorten-card ml-card" aria-label="Schnellkürzer">
        <form method="post" action="{{ route('meinlink-dashboard.store') }}" class="ml-quick-shorten-form">
            @csrf
            <div class="ml-quick-shorten-body">
                <div class="ml-quick-shorten-input-wrap">
                    <span class="ml-input-icon" aria-hidden="true">
                        <x-meinlink::icon name="external" size="16" />
                    </span>
                    <input name="destination_url"
                           type="url"
                           required
                           placeholder="Lange Zieladresse eingeben (https://...)"
                           aria-label="Lange Zieladresse">
                </div>
                <div class="ml-quick-shorten-prefix">
                    <span class="ml-prefix-label">meinlink.at/</span>
                    <input name="slug"
                           placeholder="Wunschkürzel (optional)"
                           aria-label="Wunschkürzel">
                </div>
                <button class="button primary ml-btn ml-btn-primary ml-quick-shorten-btn" type="submit">
                    <x-meinlink::icon name="plus" size="15" />
                    <span>Sofort kürzen</span>
                </button>
            </div>
        </form>
    </section>

    <section class="metrics ml-metrics" aria-label="Deine Statistik">
        <div class="ml-stat ml-stat-red">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="link" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($total, 0, ',', '.') }}</strong>
                <span>Links insgesamt</span>
            </div>
        </div>
        <div class="ml-stat ml-stat-blue">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="mouse-pointer" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($clicks, 0, ',', '.') }}</strong>
                <span>Klicks insgesamt</span>
            </div>
        </div>
        <div class="ml-stat ml-stat-green">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="calendar" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($today, 0, ',', '.') }}</strong>
                <span>Heute erstellt</span>
            </div>
        </div>
    </section>

    <section class="panel ml-card">
        <div class="panel-heading ml-card-header">
            <div class="ml-card-header-text">
                <h2 class="ml-card-title">Meine Links</h2>
                <span class="muted ml-card-subtitle">Nur deine meinlink.at-Links</span>
            </div>
            <div class="ml-card-header-actions">
                <a class="button ml-btn ml-btn-secondary ml-btn-sm" href="{{ route('meinlink-dashboard.links.export') }}" title="Alle deine Links als CSV-Tabelle herunterladen">
                    <x-meinlink::icon name="download" size="14" />
                    <span>CSV Export</span>
                </a>
            </div>
        </div>

        <form method="get" class="filters ml-filters" role="search">
            <div class="search-field ml-field-search">
                <label for="q">Links durchsuchen</label>
                <div class="ml-input-icon-wrap">
                    <span class="ml-input-icon" aria-hidden="true">
                        <x-meinlink::icon name="search" size="16" />
                    </span>
                    <input id="q" name="q" value="{{ $search }}" maxlength="150" placeholder="Kürzel, Zieladresse oder Beschreibung">
                </div>
            </div>
            <div class="ml-field-select">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="all">Alle Links</option>
                    <option value="active" @selected($status === 'active')>Aktiv</option>
                    <option value="inactive" @selected($status === 'inactive')>Inaktiv / abgelaufen</option>
                </select>
            </div>
            <div class="ml-field-select">
                <label for="sort">Sortieren</label>
                <select id="sort" name="sort">
                    <option value="newest" @selected($sort === 'newest')>Neueste zuerst</option>
                    <option value="oldest" @selected($sort === 'oldest')>Älteste zuerst</option>
                    <option value="clicks" @selected($sort === 'clicks')>Meiste Klicks</option>
                </select>
            </div>
            <div class="ml-filter-actions">
                <button class="button ml-btn ml-btn-secondary" type="submit">
                    <x-meinlink::icon name="filter" size="15" />
                    <span>Suchen</span>
                </button>
                @if($search !== '' || $status !== 'all' || $sort !== 'newest' || !empty($tag))
                    <a class="ml-filter-reset" href="{{ route('meinlink-dashboard.index') }}">Zurücksetzen</a>
                @endif
            </div>
        </form>

        @if(!empty($tag))
            <div class="ml-active-filter-bar">
                <span class="ml-filter-label">Aktiver Tag-Filter:</span>
                <span class="ml-tag ml-tag-active">
                    <x-meinlink::icon name="tag" size="12" />
                    <span>{{ $tag }}</span>
                </span>
                <a href="{{ route('meinlink-dashboard.index', array_filter(['q' => $search, 'status' => $status, 'sort' => $sort])) }}" class="ml-filter-reset">Filter aufheben</a>
            </div>
        @endif

        <div class="link-list ml-link-list">
        @forelse($links as $link)
            <article class="link-row ml-link-row">
                <div class="link-main ml-link-main">
                    <div class="link-title ml-link-title">
                        <a href="{{ route('meinlink-dashboard.show', $link->id) }}" class="ml-link-slug">meinlink.at/{{ $link->slug }}</a>
                        <x-meinlink::badge :status="\App\Support\MeinlinkLinkState::label($link)" />
                        @if(!empty($link->tags) && is_array($link->tags))
                            @foreach($link->tags as $t)
                                <a href="{{ route('meinlink-dashboard.index', ['tag' => $t]) }}" class="ml-tag" title="Nach diesem Tag filtern">
                                    <x-meinlink::icon name="tag" size="11" />
                                    <span>{{ $t }}</span>
                                </a>
                            @endforeach
                        @endif
                    </div>
                    <p class="destination ml-link-destination" title="{{ $link->destination_url }}">{{ $link->destination_url }}</p>
                    @if($link->description)
                        <p class="ml-link-desc">{{ $link->description }}</p>
                    @endif
                    <div class="ml-link-meta">
                        <small class="muted">
                            <span class="ml-meta-item">
                                <x-meinlink::icon name="mouse-pointer" size="12" />
                                <strong>{{ number_format($link->click_count, 0, ',', '.') }}</strong> Klicks
                            </span>
                            <span class="ml-meta-sep">·</span>
                            <span class="ml-meta-item">
                                <x-meinlink::icon name="calendar" size="12" />
                                Erstellt am {{ $link->created_at->format('d.m.Y') }}
                            </span>
                            @if($link->expires_at)
                                <span class="ml-meta-sep">·</span>
                                <span class="ml-meta-item">
                                    <x-meinlink::icon name="clock" size="12" />
                                    Gültig bis {{ $link->expires_at->format('d.m.Y') }}
                                </span>
                            @endif
                        </small>
                    </div>
                </div>
                <div class="row-actions ml-row-actions">
                    <button class="button ml-btn ml-btn-secondary ml-btn-sm" type="button" data-copy="{{ $link->short_url }}" aria-label="Link {{ $link->slug }} kopieren">
                        <x-meinlink::icon name="copy" size="14" />
                        <span>Kopieren</span>
                    </button>
                    <a class="button ml-btn ml-btn-flat ml-btn-sm" href="{{ route('meinlink-dashboard.show', $link->id) }}">
                        <span>Details →</span>
                    </a>
                </div>
            </article>
        @empty
            <div class="empty ml-empty-wrapper">
                <x-meinlink::empty-state
                    :title="$total ? 'Keine passenden Links gefunden.' : 'Dein erster kurzer Link wartet.'"
                    :description="$total ? 'Versuche einen anderen Suchbegriff oder setze den Filter zurück.' : 'Eine lange Adresse rein, einen teilbaren meinlink.at-Link raus.'"
                    icon="empty-links">
                    <a class="button primary ml-btn ml-btn-primary" href="{{ route('meinlink-dashboard.create') }}">
                        <x-meinlink::icon name="plus" size="16" />
                        <span>Link erstellen</span>
                    </a>
                </x-meinlink::empty-state>
            </div>
        @endforelse
        </div>

        @if($links->hasPages())
            <nav class="pagination ml-pagination" aria-label="Seitennavigation">
                @if($links->previousPageUrl())
                    <a class="button ml-btn ml-btn-secondary ml-btn-sm" href="{{ $links->previousPageUrl() }}">
                        <x-meinlink::icon name="arrow-left" size="14" />
                        <span>← Zurück</span>
                    </a>
                @endif
                <span class="ml-pagination-info">Seite {{ $links->currentPage() }} von {{ $links->lastPage() }}</span>
                @if($links->nextPageUrl())
                    <a class="button ml-btn ml-btn-secondary ml-btn-sm" href="{{ $links->nextPageUrl() }}">
                        <span>Weiter →</span>
                        <x-meinlink::icon name="arrow-right" size="14" />
                    </a>
                @endif
            </nav>
        @endif
    </section>

    <p class="muted footnote ml-footnote">Deine Links anderer Domains findest du im <a href="https://my.href.nz" target="_blank" rel="noopener noreferrer">öffentlichen Ternis-Dashboard</a>.</p>
@endsection
