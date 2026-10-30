@extends('meinlink-dashboard.layout')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">DEIN LINK-ÜBERBLICK</p><h1>Weniger lang.<br>Mehr Überblick.</h1><p>Erstelle, teile und verwalte deine meinlink.at-Links.</p></div><a class="button primary" href="{{ route('meinlink-dashboard.create') }}">+ Neuen Link erstellen</a></div>
    <section class="metrics" aria-label="Deine Statistik">
        <div><strong>{{ number_format($total, 0, ',', '.') }}</strong><span>Links insgesamt</span></div>
        <div><strong>{{ number_format($clicks, 0, ',', '.') }}</strong><span>Klicks insgesamt</span></div>
        <div><strong>{{ number_format($today, 0, ',', '.') }}</strong><span>Heute erstellt</span></div>
    </section>
    <section class="panel">
        <div class="panel-heading"><h2>Meine Links</h2><span class="muted">Nur deine meinlink.at-Links</span></div>
        <form method="get" class="filters" role="search">
            <div class="search-field"><label for="q">Links durchsuchen</label><input id="q" name="q" value="{{ $search }}" maxlength="150" placeholder="Kürzel, Zieladresse oder Beschreibung"></div>
            <div><label for="status">Status</label><select id="status" name="status"><option value="all">Alle Links</option><option value="active" @selected($status === 'active')>Aktiv</option><option value="inactive" @selected($status === 'inactive')>Inaktiv / abgelaufen</option></select></div>
            <div><label for="sort">Sortieren</label><select id="sort" name="sort"><option value="newest" @selected($sort === 'newest')>Neueste zuerst</option><option value="oldest" @selected($sort === 'oldest')>Älteste zuerst</option><option value="clicks" @selected($sort === 'clicks')>Meiste Klicks</option></select></div>
            <button class="button" type="submit">Suchen</button>@if($search !== '' || $status !== 'all' || $sort !== 'newest')<a href="{{ route('meinlink-dashboard.index') }}">Zurücksetzen</a>@endif
        </form>
        <div class="link-list">
        @forelse($links as $link)
            <article class="link-row">
                <div class="link-main"><div class="link-title"><a href="{{ route('meinlink-dashboard.show', $link->id) }}">meinlink.at/{{ $link->slug }}</a><span class="status">{{ \App\Support\MeinlinkLinkState::label($link) }}</span></div><p class="destination">{{ $link->destination_url }}</p>@if($link->description)<p>{{ $link->description }}</p>@endif<small class="muted">{{ number_format($link->click_count, 0, ',', '.') }} Klicks · Erstellt am {{ $link->created_at->format('d.m.Y') }}</small></div>
                <div class="row-actions"><button class="button" type="button" data-copy="{{ $link->short_url }}" aria-label="Link {{ $link->slug }} kopieren">Kopieren</button><a class="button" href="{{ route('meinlink-dashboard.show', $link->id) }}">Details →</a></div>
            </article>
        @empty
            <div class="empty"><h3>{{ $total ? 'Keine passenden Links gefunden.' : 'Dein erster kurzer Link wartet.' }}</h3><p>{{ $total ? 'Versuche einen anderen Suchbegriff oder setze den Filter zurück.' : 'Eine lange Adresse rein, einen teilbaren meinlink.at-Link raus.' }}</p><a class="button primary" href="{{ route('meinlink-dashboard.create') }}">Link erstellen</a></div>
        @endforelse
        </div>
        @if($links->hasPages())<nav class="pagination" aria-label="Seitennavigation">@if($links->previousPageUrl())<a class="button" href="{{ $links->previousPageUrl() }}">← Zurück</a>@endif<span>Seite {{ $links->currentPage() }} von {{ $links->lastPage() }}</span>@if($links->nextPageUrl())<a class="button" href="{{ $links->nextPageUrl() }}">Weiter →</a>@endif</nav>@endif
    </section>
    <p class="muted footnote">Deine Links anderer Domains findest du im <a href="https://my.href.nz">öffentlichen Ternis-Dashboard</a>.</p>
@endsection
