# Links Per Domain

> How the network splits across domains. Aggregate counts only. Only public domains are listed here — hostnames added by users; built-in system domains (like `href.nz`) are excluded, but their traffic is included in the totals on the overview. [HTML version]({{ url('/pages/stats/domains') }}) · [Overview]({{ url('/pages/stats.md') }})

| Domain | Links | Clicks |
|---|---:|---:|
@foreach ($domains as $domain)
| {{ $domain->hostname }} | {{ number_format($domain->links_count) }} | {{ number_format($domain->links_sum_click_count ?? 0) }} |
@endforeach
