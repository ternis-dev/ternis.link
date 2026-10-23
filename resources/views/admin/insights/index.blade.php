@include('admin.stats.insights', [
    'section' => $section ?? 'insights',
    'stats' => $stats,
    'params' => $params ?? [],
])
