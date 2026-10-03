<?php

namespace Tests\Unit;

use App\Services\RecurrenceService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class RecurrenceServiceTest extends TestCase
{
    public function test_daily_recurrence_expands(): void
    {
        $start = Carbon::parse('2026-01-05 09:00');
        $end = $start->copy()->addHour();

        $occurrences = app(RecurrenceService::class)->occurrences($start, $end, [
            'frequency' => 'daily',
            'interval' => 1,
            'count' => 4,
        ]);

        $this->assertCount(4, $occurrences);
        $this->assertSame('2026-01-08 09:00', $occurrences[3]['start']->format('Y-m-d H:i'));
    }

    public function test_weekly_recurrence_honours_selected_days(): void
    {
        $start = Carbon::parse('2026-01-05 09:00');
        $end = $start->copy()->addHour();

        $occurrences = app(RecurrenceService::class)->occurrences($start, $end, [
            'frequency' => 'weekly',
            'interval' => 1,
            'count' => 4,
            'days' => [1, 3],
        ]);

        foreach ($occurrences as $occurrence) {
            $this->assertContains((int) $occurrence['start']->dayOfWeekIso, [1, 3]);
        }

        $this->assertCount(4, $occurrences);
    }

    public function test_none_frequency_returns_nothing(): void
    {
        $this->assertSame([], app(RecurrenceService::class)->occurrences(now(), now()->addHour(), ['frequency' => 'none']));
    }
}
