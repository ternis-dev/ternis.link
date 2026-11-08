@extends('meinlink-dashboard.layout')
@section('title', 'Link-Details')

@section('content')
    <a class="back ml-back-link" href="{{ route('meinlink-dashboard.index') }}">
        <x-meinlink::icon name="arrow-left" size="14" />
        <span>← Meine Links</span>
    </a>

    <div class="page-heading ml-page-header">
        <div class="ml-page-header-main">
            <p class="eyebrow ml-eyebrow">DEIN KURZLINK</p>
            <h1 class="short-heading ml-page-title ml-slug-title">meinlink.at/{{ $link->slug }}</h1>
            <p class="destination ml-link-destination" title="{{ $link->destination_url }}">{{ $link->destination_url }}</p>
        </div>
        <div class="ml-page-header-actions">
            <button class="button primary ml-btn ml-btn-primary" data-copy="{{ $link->short_url }}" type="button">
                <x-meinlink::icon name="copy" size="16" />
                <span>Link kopieren</span>
            </button>
        </div>
    </div>

    <div class="detail-grid ml-detail-grid">
        <section class="panel detail-panel ml-card">
            <div class="ml-card-header">
                <h2 class="ml-card-title">Alles zum Link</h2>
            </div>
            <dl class="ml-dl">
                <div class="ml-dl-row">
                    <dt>Status</dt>
                    <dd>
                        <x-meinlink::badge :status="\App\Support\MeinlinkLinkState::label($link)" />
                    </dd>
                </div>
                <div class="ml-dl-row">
                    <dt>Passwort</dt>
                    <dd>
                        @if($link->password_hash)
                            <span class="ml-tag ml-tag-active">
                                <x-meinlink::icon name="lock" size="13" />
                                <span>Aktiv</span>
                            </span>
                        @else
                            <span class="ml-text-muted">Nicht gesetzt</span>
                        @endif
                    </dd>
                </div>
                <div class="ml-dl-row">
                    <dt>Browserprüfung</dt>
                    <dd>
                        @if($link->bot_protection)
                            <span class="ml-tag ml-tag-active">
                                <x-meinlink::icon name="shield" size="13" />
                                <span>Aktiv</span>
                            </span>
                        @else
                            <span class="ml-text-muted">Nicht aktiviert</span>
                        @endif
                    </dd>
                </div>
                <div class="ml-dl-row">
                    <dt>Beschreibung</dt>
                    <dd>{{ $link->description ?: 'Keine Beschreibung' }}</dd>
                </div>
                @if(!empty($link->tags) && is_array($link->tags))
                    <div class="ml-dl-row">
                        <dt>Tags</dt>
                        <dd>
                            <div class="ml-tags-wrap">
                                @foreach($link->tags as $t)
                                    <span class="ml-tag"><x-meinlink::icon name="tag" size="11"/> {{ $t }}</span>
                                @endforeach
                            </div>
                        </dd>
                    </div>
                @endif
                @if($link->utm_source || $link->utm_medium || $link->utm_campaign)
                    <div class="ml-dl-row">
                        <dt>Kampagne / UTM</dt>
                        <dd class="ml-text-muted">
                            {{ implode(' · ', array_filter([$link->utm_source ? 'Quelle: '.$link->utm_source : null, $link->utm_medium ? 'Medium: '.$link->utm_medium : null, $link->utm_campaign ? 'Kampagne: '.$link->utm_campaign : null])) }}
                        </dd>
                    </div>
                @endif
                <div class="ml-dl-row">
                    <dt>Erstellt</dt>
                    <dd>{{ $link->created_at->format('d.m.Y, H:i') }}</dd>
                </div>
                <div class="ml-dl-row">
                    <dt>Gültig bis</dt>
                    <dd>{{ $link->expires_at?->format('d.m.Y, H:i') ?? 'Kein Ablaufdatum' }}</dd>
                </div>
            </dl>

            <div class="row-actions ml-detail-actions">
                @unless($blocked)
                    <a class="button ml-btn ml-btn-secondary" href="{{ route('meinlink-dashboard.edit', $link->id) }}">
                        <x-meinlink::icon name="edit" size="15" />
                        <span>Bearbeiten</span>
                    </a>
                @endunless

                <a class="button ml-btn ml-btn-secondary" href="{{ route('meinlink-dashboard.qr', $link->id) }}">
                    <x-meinlink::icon name="qr" size="15" />
                    <span>QR-Code herunterladen</span>
                </a>

                <a class="button ml-btn ml-btn-secondary" href="{{ route('meinlink-dashboard.qr', ['link' => $link->id, 'format' => 'svg']) }}" title="Vektor-Format für Druck und Flyer">
                    <x-meinlink::icon name="download" size="15" />
                    <span>Vektor-QR (SVG)</span>
                </a>
            </div>

            @if(! $blocked && ($link->is_active || ! $link->isExpired()))
                <form class="pause-form ml-pause-form" method="post" action="{{ route('meinlink-dashboard.toggle', $link->id) }}">
                    @csrf
                    <button class="text-button ml-btn ml-btn-subtle" type="submit">
                        <x-meinlink::icon :name="$link->is_active ? 'pause' : 'play'" size="15" />
                        <span>{{ $link->is_active ? 'Link pausieren' : 'Link aktivieren' }}</span>
                    </button>
                </form>
            @endif

            @if($blocked)
                <div class="notice error ml-notice ml-notice-error">
                    <div class="ml-notice-icon" aria-hidden="true">
                        <x-meinlink::icon name="alert" size="18" />
                    </div>
                    <div class="ml-notice-body">
                        <strong>Dieser Link ist gesperrt.</strong>
                        <p>Eine menschliche Überprüfung ist möglich. <a href="mailto:abuse@ternis.dev?subject={{ rawurlencode('Überprüfung: '.$link->short_url) }}">Überprüfung anfragen</a></p>
                    </div>
                </div>
            @elseif($link->isExpired())
                <p class="muted ml-notice-note">Dieser Link ist abgelaufen. Ändere oder entferne unter „Bearbeiten“ zuerst das Ablaufdatum.</p>
            @endif
        </section>

        <section class="panel detail-panel ml-card">
            <div class="ml-card-header">
                <h2 class="ml-card-title">Klicks im Blick</h2>
            </div>
            <p class="big-number ml-big-stat">
                {{ number_format($link->click_count, 0, ',', '.') }}
                <span>Klicks insgesamt</span>
            </p>

            <h3 class="ml-section-subtitle">Die letzten 7 Tage</h3>
            <table class="ml-table">
                <thead>
                    <tr>
                        <th scope="col">Tag</th>
                        <th scope="col">Klicks</th>
                    </tr>
                </thead>
                <tbody>
                    @php($maxCount = max(1, ...array_map(fn($d) => $daily[now()->subDays($d)->format('Y-m-d')] ?? 0, range(0, 6))))
                    @for($i = 0; $i < 7; $i++)
                        @php($day = now()->subDays($i))
                        @php($count = $daily[$day->format('Y-m-d')] ?? 0)
                        <tr>
                            <th scope="row">{{ $day->format('d.m.Y') }}</th>
                            <td>
                                <span class="daily-count ml-bar-cell">
                                    <span class="daily-bar ml-flat-bar" style="width: {{ $count > 0 ? max(8, round(($count / $maxCount) * 80)) : 0 }}px" aria-hidden="true"></span>
                                    <span>{{ number_format($count, 0, ',', '.') }}</span>
                                </span>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
            <p class="muted ml-table-note">Zeiten in {{ config('app.timezone') }}. Klickzahlen enthalten auch wiederholte Aufrufe.</p>
        </section>
    </div>
@endsection
