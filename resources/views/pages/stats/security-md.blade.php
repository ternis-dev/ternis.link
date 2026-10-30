# Network Security

- Security evaluations: {{ $stats['threat_evaluations'] }}
- Threats Quarantined: {{ $stats['threats_quarantined'] }}
- Bot protection enabled: {{ $stats['threats_bot_protected'] }}
- Evaluations today: {{ $stats['threat_evaluations_today'] }}
- Benign destinations: {{ $stats['threats_cleared'] ?? 0 }}
- Average threat score: {{ ($stats['threats_avg_score'] ?? 0) }}/100

@if (!empty($breakdown['categories']))
## Threat classification breakdown

@foreach ($breakdown['categories'] as $catKey => $cat)
- {{ $cat['label'] }}: {{ $cat['percent'] }}% ({{ $cat['count'] }})
@endforeach
@endif

## Counting methodology

Quarantine totals include recorded quarantine actions and links carrying a legacy quarantine tag without a matching action. Repeat actions can include rescans; this is not a count of currently blocked unique links. Historical links deleted before logging cannot be reconstructed. Bot protection counts recorded enablement actions, not visitor challenges or verified visitors.
