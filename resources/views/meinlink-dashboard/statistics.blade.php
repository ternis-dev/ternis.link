@extends('meinlink-dashboard.layout')
@section('title', 'Statistiken')

@section('content')
    <div class="page-heading ml-page-header">
        <div class="ml-page-header-main">
            <p class="eyebrow ml-eyebrow">DEINE REICHWEITE</p>
            <h1 class="ml-page-title">Jeder Klick zählt.</h1>
            <p class="ml-page-desc">So werden deine meinlink.at-Links in den letzten {{ $days }} Tagen genutzt.</p>
        </div>
        <div class="ml-page-header-actions">
            <a class="button ml-btn ml-btn-secondary" href="{{ route('meinlink-dashboard.statistics.export', ['days' => $days]) }}">
                <x-meinlink::icon name="download" size="15" />
                <span>Statistik als CSV herunterladen</span>
            </a>
        </div>
    </div>

    <nav class="period-picker ml-period-picker" aria-label="Zeitraum">
        @foreach([7, 30, 90] as $range)
            <a class="button {{ $days === $range ? 'primary is-active' : '' }} ml-btn {{ $days === $range ? 'ml-btn-primary' : 'ml-btn-secondary' }}"
               href="{{ route('meinlink-dashboard.statistics', ['days' => $range]) }}"
               @if($days === $range) aria-current="page" @endif>
                <x-meinlink::icon name="calendar" size="14" />
                <span>{{ $range }} Tage</span>
            </a>
        @endforeach
    </nav>

    <section class="metrics ml-metrics" aria-label="Zusammenfassung im gewählten Zeitraum">
        <div class="ml-stat ml-stat-red">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="mouse-pointer" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($total, 0, ',', '.') }}</strong>
                <span>Klicks</span>
            </div>
        </div>
        <div class="ml-stat ml-stat-blue">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="chart" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($activeDays, 0, ',', '.') }}</strong>
                <span>Tage mit Klicks</span>
            </div>
        </div>
        <div class="ml-stat ml-stat-green">
            <div class="ml-stat-icon-wrap" aria-hidden="true">
                <x-meinlink::icon name="plus" size="22" />
            </div>
            <div class="ml-stat-content">
                <strong>{{ number_format($created, 0, ',', '.') }}</strong>
                <span>Neue Links</span>
            </div>
        </div>
    </section>

    @if($total === 0)
        <div class="notice ml-notice ml-notice-info" role="status">
            <div class="ml-notice-icon" aria-hidden="true">
                <x-meinlink::icon name="info" size="18" />
            </div>
            <div class="ml-notice-body">
                In diesem Zeitraum wurden noch keine Klicks erfasst. Teile einen deiner Links oder wähle einen längeren Zeitraum.
            </div>
        </div>
    @endif

    <div class="detail-grid ml-detail-grid">
        <section class="panel detail-panel ml-card">
            <div class="ml-card-header">
                <div class="ml-card-header-text">
                    <h2 class="ml-card-title">Klicks pro Tag</h2>
                    <p class="muted ml-card-subtitle">{{ $daily->first()['label'] }} bis {{ $daily->last()['label'] }} · {{ config('app.timezone') }}</p>
                </div>
            </div>
            <div class="daily-scroll ml-scroll-panel" tabindex="0" role="region" aria-label="Tägliche Klickzahlen">
                <table class="ml-table">
                    <thead>
                        <tr>
                            <th scope="col">Tag</th>
                            <th scope="col">Klicks</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php($maxDayCount = max(1, $daily->max('count')))
                    @foreach($daily->reverse() as $day)
                        <tr>
                            <th scope="row">{{ $day['label'] }}</th>
                            <td>
                                <span class="daily-count ml-bar-cell">
                                    <span class="daily-bar ml-flat-bar" style="width: {{ $total > 0 && $day['count'] > 0 ? max(8, round(($day['count'] / $maxDayCount) * 80)) : 0 }}px" aria-hidden="true"></span>
                                    <span>{{ number_format($day['count'], 0, ',', '.') }}</span>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel detail-panel ml-card">
            <div class="ml-card-header">
                <div class="ml-card-header-text">
                    <h2 class="ml-card-title">Deine meistgeklickten Links</h2>
                    <p class="muted ml-card-subtitle">Bis zu fünf Links · nur Klicks im gewählten Zeitraum</p>
                </div>
            </div>
            <div class="ml-card-body">
                @forelse($topLinks as $index => $link)
                    <div class="top-link ml-top-link-row">
                        <div class="ml-top-link-rank">#{{ $index + 1 }}</div>
                        <div class="ml-top-link-info">
                            <a href="{{ route('meinlink-dashboard.show', $link->id) }}" class="ml-top-link-slug">meinlink.at/{{ $link->slug }}</a>
                            <span class="ml-top-link-dest">{{ $link->destination_url }}</span>
                        </div>
                        <div class="ml-top-link-stat">
                            <strong>{{ number_format($link->period_clicks, 0, ',', '.') }}</strong>
                            <small class="muted">Klicks</small>
                        </div>
                    </div>
                @empty
                    <div class="ml-empty-subtle">
                        <p>Noch keine Link-Aufrufe in diesem Zeitraum.</p>
                        <a class="button ml-btn ml-btn-secondary ml-btn-sm" href="{{ route('meinlink-dashboard.index') }}">
                            <span>Zu meinen Links</span>
                        </a>
                    </div>
                @endforelse
            </div>
            <p class="muted footnote ml-footnote">Die Statistik enthält wiederholte Aufrufe. Sie zählt keine eindeutigen Personen. Entfernte Links und Aufrufe anderer Domains sind hier nicht enthalten.</p>
        </section>
    </div>
@endsection
