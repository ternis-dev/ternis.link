# Network Security

- Security evaluations: {{ $stats['threat_evaluations'] }}
- Threats Quarantined: {{ $stats['threats_quarantined'] }}
- Bot protection enabled: {{ $stats['threats_bot_protected'] }}
- Evaluations today: {{ $stats['threat_evaluations_today'] }}

## Counting methodology

Quarantine totals include recorded quarantine actions and links carrying a legacy quarantine tag without a matching action. Repeat actions can include rescans; this is not a count of currently blocked unique links. Historical links deleted before logging cannot be reconstructed. Bot protection counts recorded enablement actions, not visitor challenges or verified visitors.
