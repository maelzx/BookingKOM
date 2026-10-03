<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Expands a simple recurrence rule into concrete occurrence windows.
 *
 * Supported frequencies: daily, weekly, monthly with an interval, an optional
 * weekday list (weekly), a maximum count and an optional until date.
 */
class RecurrenceService
{
    public const MAX_OCCURRENCES = 52;

    /**
     * @param  array<string, mixed>  $rule
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    public function occurrences(CarbonInterface $start, CarbonInterface $end, array $rule, int $limit = self::MAX_OCCURRENCES): array
    {
        $frequency = (string) ($rule['frequency'] ?? 'none');

        if (! in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
            return [];
        }

        $interval = max(1, (int) ($rule['interval'] ?? 1));
        $count = min($limit, max(1, (int) ($rule['count'] ?? 4)));
        $until = ! empty($rule['until']) ? Carbon::parse((string) $rule['until'])->endOfDay() : null;
        $days = array_map('intval', (array) ($rule['days'] ?? []));

        $duration = Carbon::parse($start)->diffInMinutes($end);
        $cursor = Carbon::parse($start);
        $results = [];

        $guard = 0;

        while (count($results) < $count && $guard++ < 1000) {
            if ($until && $cursor->gt($until)) {
                break;
            }

            $matches = match ($frequency) {
                'daily' => true,
                'monthly' => true,
                'weekly' => $days === [] || in_array((int) $cursor->dayOfWeekIso, $days, true),
                default => false,
            };

            if ($matches) {
                $results[] = [
                    'start' => $cursor->copy(),
                    'end' => $cursor->copy()->addMinutes((int) $duration),
                ];
            }

            $cursor = match ($frequency) {
                'daily' => $cursor->copy()->addDays($interval),
                'weekly' => $cursor->copy()->addDays($frequency === 'weekly' && $days !== [] ? 1 : $interval * 7),
                'monthly' => $cursor->copy()->addMonthsNoOverflow($interval),
                default => $cursor->copy()->addDay(),
            };
        }

        return $results;
    }
}
