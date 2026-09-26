# Top Links

> Most-clicked short links across the network. Slugs are public; no owners, no destinations. [HTML version]({{ url('/pages/stats/links') }}) · [Overview]({{ url('/pages/stats.md') }})

| Short Link | Clicks | Status | Created |
|---|---:|---|---|
@foreach ($links as $link)
| {{ ($link->domain->hostname ?? 'href.nz').'/'.$link->slug }} | {{ number_format($link->click_count) }} | @if ($link->is_removed) Removed @elseif ($link->is_active && ! $link->isExpired()) Active @elseif ($link->isExpired()) Expired @else Disabled @endif | {{ $link->created_at?->format('M d, Y') }} |
@endforeach
