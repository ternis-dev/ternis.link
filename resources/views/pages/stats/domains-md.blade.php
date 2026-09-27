# Links Per Domain

> How the network splits across domains. Aggregate counts only. Platform domains are listed for everyone; your own custom domains appear here once you're signed in — other users' hostnames are never shown. [HTML version]({{ url('/pages/stats/domains') }}) · [Overview]({{ url('/pages/stats.md') }})

| Domain | Links | Clicks |
|---|---:|---:|
@foreach ($domains as $domain)
| {{ $domain->hostname }} | {{ number_format($domain->links_count) }} | {{ number_format($domain->links_sum_click_count ?? 0) }} |
@endforeach
