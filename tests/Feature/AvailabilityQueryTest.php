<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Models\Booking;
use App\Models\Resource;
use App\Services\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AvailabilityQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_generation_uses_a_bounded_number_of_queries(): void
    {
        $resource = Resource::factory()->create([
            'approval_mode' => ApprovalMode::None,
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '08:00',
            'available_to' => '18:00',
            'booking_rules' => [
                'min_duration_minutes' => 15,
                'max_duration_minutes' => 480,
                'buffer_minutes' => 0,
            ],
        ]);

        $day = Carbon::parse('next monday');
        $booking = Booking::factory()->create([
            'starts_at' => $day->copy()->setTime(9, 0),
            'ends_at' => $day->copy()->setTime(10, 0),
        ]);
        $booking->resources()->attach($resource->id, ['status' => 'not_required']);

        // Warm the settings cache so it does not count as a query.
        app(BookingAvailability::class)->slotsForDate($resource, $day);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $slots = app(BookingAvailability::class)->slotsForDate($resource, $day);

        $this->assertNotEmpty($slots);
        $this->assertLessThanOrEqual(4, $queries, "Expected a bounded query count, got {$queries}.");
    }
}
