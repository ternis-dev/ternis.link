<?php

namespace App\Support;

class StatsTrends
{
    /** Compare complete days only: today's partial counts must not skew growth. */
    public static function summarize(array $activity): array
    {
        $end = now()->startOfDay();
        $currentStart = $end->copy()->subDays(7);
        $previousStart = $end->copy()->subDays(14);
        $metrics = [];

        foreach ($activity as $key => $series) {
            $days = array_combine($series['labels'], $series['values']);
            $current = $previous = 0;
            $labels = $values = [];
            for ($day = $previousStart->copy(); $day->lt($end); $day->addDay()) {
                $label = $day->toDateString();
                $value = (int) ($days[$day->format('M j')] ?? 0);
                $labels[] = $label;
                $values[] = $value;
                if ($day->lt($currentStart)) {
                    $previous += $value;
                } else {
                    $current += $value;
                }
            }
            $metrics[$key] = [
                'current' => $current,
                'previous' => $previous,
                'change' => $previous === 0 ? ($current === 0 ? 'No change' : 'No previous activity') : sprintf('%+.1f%%', ($current - $previous) / $previous * 100),
                'daily_average' => round($current / 7, 1),
                'labels' => $labels,
                'values' => $values,
            ];
        }

        return [
            'current_period' => $currentStart->toDateString().' – '.$end->copy()->subDay()->toDateString(),
            'previous_period' => $previousStart->toDateString().' – '.$currentStart->copy()->subDay()->toDateString(),
            'metrics' => $metrics,
        ];
    }
}
