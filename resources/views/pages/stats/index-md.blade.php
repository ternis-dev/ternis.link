# Network Stats

> Public, aggregate-only analytics for the whole ternis.link network. No personal data — counts and daily totals, nothing else.
> Data refreshes every 10 minutes. [HTML version]({{ url('/pages/stats') }})

- Links Created (All Time): {{ number_format($stats['total_links']) }}
- Active Links: {{ number_format($stats['active_links']) }}
- Removed Links: {{ number_format($stats['removed_links']) }}
- Total Clicks (All Time): {{ number_format($stats['total_clicks']) }}
- Links Today: {{ number_format($stats['links_today']) }}
- Clicks Today: {{ number_format($stats['clicks_today']) }}

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
