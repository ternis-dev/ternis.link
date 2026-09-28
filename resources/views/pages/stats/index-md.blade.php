# Network Stats

> Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts and daily totals, nothing else.
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
