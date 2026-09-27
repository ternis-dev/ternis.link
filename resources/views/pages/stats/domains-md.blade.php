# Links Per Domain

> How the network splits across domains. Aggregate counts only. Custom domains only — hostnames added by users; built-in system domains (like `href.nz`) are excluded here, but their traffic still counts toward the totals on the overview. [HTML version]({{ url('/pages/stats/domains') }}) · [Overview]({{ url('/pages/stats.md') }})

| Domain | Links | Clicks |
|---|---:|---:|
@foreach ($domains as $domain)
| {{ $domain->hostname }} | {{ number_format($domain->links_count) }} | {{ number_format($domain->links_sum_click_count ?? 0) }} |
@endforeach
