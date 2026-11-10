# Network Trends

Last 7 complete days: {{ $trends['current_period'] }}
Compared with: {{ $trends['previous_period'] }}
Timezone: {{ config('app.timezone') }}. Today is excluded.

| Metric | Last 7 days | Previous 7 days | Change | Daily average |
|---|---:|---:|---|---:|
@foreach (['links' => 'Links created', 'clicks' => 'Redirect clicks', 'qr' => 'QR codes generated'] as $key => $label)
| {{ $label }} | {{ $trends['metrics'][$key]['current'] }} | {{ $trends['metrics'][$key]['previous'] }} | {{ $trends['metrics'][$key]['change'] }} | {{ $trends['metrics'][$key]['daily_average'] }} |
@endforeach

Aggregate counts only; counts are not unique people or conversion rates.
