@extends('meinlink-dashboard.layout')
@section('title', 'Statistiken')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">DEINE REICHWEITE</p><h1>Jeder Klick zählt.</h1><p>So werden deine meinlink.at-Links in den letzten {{ $days }} Tagen genutzt.</p></div>
        <a class="button" href="{{ route('meinlink-dashboard.statistics.export', ['days' => $days]) }}">Statistik als CSV herunterladen</a>
    </div>
    <nav class="period-picker" aria-label="Zeitraum">
        @foreach([7, 30, 90] as $range)
            <a class="button {{ $days === $range ? 'primary' : '' }}" href="{{ route('meinlink-dashboard.statistics', ['days' => $range]) }}" @if($days === $range) aria-current="page" @endif>{{ $range }} Tage</a>
        @endforeach
    </nav>
    <section class="metrics" aria-label="Zusammenfassung im gewählten Zeitraum">
        <div><strong>{{ number_format($total, 0, ',', '.') }}</strong><span>Klicks</span></div>
        <div><strong>{{ number_format($activeDays, 0, ',', '.') }}</strong><span>Tage mit Klicks</span></div>
        <div><strong>{{ number_format($created, 0, ',', '.') }}</strong><span>Neue Links</span></div>
    </section>
    @if($total === 0)<div class="notice" role="status">In diesem Zeitraum wurden noch keine Klicks erfasst. Teile einen deiner Links oder wähle einen längeren Zeitraum.</div>@endif
    <div class="detail-grid">
        <section class="panel detail-panel">
            <h2>Klicks pro Tag</h2><p class="muted">{{ $daily->first()['label'] }} bis {{ $daily->last()['label'] }} · {{ config('app.timezone') }}</p>
            <div class="daily-scroll" tabindex="0" role="region" aria-label="Tägliche Klickzahlen">
                <table><thead><tr><th scope="col">Tag</th><th scope="col">Klicks</th></tr></thead><tbody>
                @foreach($daily->reverse() as $day)
                    <tr><th scope="row">{{ $day['label'] }}</th><td><span class="daily-count"><span class="daily-bar" style="width: {{ $total > 0 ? round($day['count'] / max(1, $daily->max('count')) * 80) : 0 }}px" aria-hidden="true"></span>{{ number_format($day['count'], 0, ',', '.') }}</span></td></tr>
                @endforeach
                </tbody></table>
            </div>
        </section>
        <section class="panel detail-panel">
            <h2>Deine meistgeklickten Links</h2><p class="muted">Bis zu fünf Links · nur Klicks im gewählten Zeitraum</p>
            @forelse($topLinks as $link)
                <div class="top-link"><a href="{{ route('meinlink-dashboard.show', $link->id) }}">meinlink.at/{{ $link->slug }}</a><strong>{{ number_format($link->period_clicks, 0, ',', '.') }} <small>Klicks</small></strong></div>
            @empty
                <p>Noch keine Link-Aufrufe in diesem Zeitraum.</p><a class="button" href="{{ route('meinlink-dashboard.index') }}">Zu meinen Links</a>
            @endforelse
            <p class="muted footnote">Die Statistik enthält wiederholte Aufrufe. Sie zählt keine eindeutigen Personen. Entfernte Links und Aufrufe anderer Domains sind hier nicht enthalten.</p>
        </section>
    </div>
@endsection
