# Network Activity

Last 30 days. Aggregate data, refreshed every 10 minutes.

| Day | Links | Clicks | QR codes |
|---|---:|---:|---:|
@foreach (array_reverse(array_keys($links['labels'])) as $i)
| {{ $links['labels'][$i] }} | {{ $links['values'][$i] }} | {{ $clicks['values'][$i] }} | {{ $qr['values'][$i] }} |
@endforeach
