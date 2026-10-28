# Network Stats

> Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts, heatmaps, and daily totals, nothing else.
> Data refreshes every 10 minutes. [HTML version]({{ url('/pages/stats') }})

- Links Created (All Time): {{ number_format($stats['total_links']) }}
- Active Links: {{ number_format($stats['active_links']) }}
- Removed Links: {{ number_format($stats['removed_links']) }}
- Total Clicks (All Time): {{ number_format($stats['total_clicks']) }}
- Links Today: {{ number_format($stats['links_today']) }}
- Clicks Today: {{ number_format($stats['clicks_today']) }}
- Direct-URL Redirects (All Time): {{ number_format($stats['direct_url_clicks']) }}
- Direct-URL Redirects Today: {{ number_format($stats['direct_url_clicks_today']) }}
- QR Codes Generated (All Time): {{ number_format($stats['qr_codes']) }}
- QR Codes Today: {{ number_format($stats['qr_codes_today']) }}

## Links Created Per Day (last 30 days)

| Day | Links |
|---|---:|
@foreach ($creationLabels as $i => $label)
| {{ $label }} | {{ number_format($creationValues[$i]) }} |
@endforeach

## Clicks Per Day (last 30 days)

| Day | Clicks |
|---|---:|
@foreach ($clickLabels as $i => $label)
| {{ $label }} | {{ number_format($clickValues[$i]) }} |
@endforeach

## QR Codes Per Day (last 30 days)

| Day | QR codes |
|---|---:|
@foreach ($qrLabels as $i => $label)
| {{ $label }} | {{ number_format($qrValues[$i]) }} |
@endforeach
@php
$weekdayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$markdownHeatmaps = [
    'Clicks' => $heatmaps['hourly']['clicks'] ?? array_fill(0, 7, array_fill(0, 24, 0)),
    'Links Created' => $heatmaps['hourly']['links'] ?? array_fill(0, 7, array_fill(0, 24, 0)),
    'QR Codes' => $heatmaps['hourly']['qr'] ?? array_fill(0, 7, array_fill(0, 24, 0)),
];
@endphp
@foreach ($markdownHeatmaps as $metricTitle => $grid)

## Network Activity Heatmap — {{ $metricTitle }} (Weekday × UTC Hour, last 90 days)

| Weekday | 00–05 | 06–11 | 12–17 | 18–23 | Total |
|---|---:|---:|---:|---:|---:|
@foreach ($weekdayNames as $w => $dayName)
@php
$row = $grid[$w] ?? array_fill(0, 24, 0);
$q1 = array_sum(array_slice($row, 0, 6));
$q2 = array_sum(array_slice($row, 6, 6));
$q3 = array_sum(array_slice($row, 12, 6));
$q4 = array_sum(array_slice($row, 18, 6));
@endphp
| {{ $dayName }} | {{ number_format($q1) }} | {{ number_format($q2) }} | {{ number_format($q3) }} | {{ number_format($q4) }} | {{ number_format($q1 + $q2 + $q3 + $q4) }} |
@endforeach
@endforeach
