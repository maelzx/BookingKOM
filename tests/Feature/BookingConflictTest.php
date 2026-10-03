<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Enums\BookingStatus;
use App\Enums\ResourceStatus;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\ResourceBlockedPeriod;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingConflictTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_overlapping_booking_is_rejected(): void
    {
        $resource = $this->resource();
        $user = User::factory()->create();

        $start = Carbon::parse('next monday 09:00');
        $this->book($resource, $start, $start->copy()->addHour(), $user);

        $this->expectException(BookingConflictException::class);

        $this->book($resource, $start->copy()->addMinutes(30), $start->copy()->addMinutes(90), User::factory()->create());
    }

    public function test_back_to_back_bookings_are_allowed(): void
    {
        $resource = $this->resource();
        $start = Carbon::parse('next monday 09:00');

        $first = $this->book($resource, $start, $start->copy()->addHour(), User::factory()->create());
        $second = $this->book($resource, $start->copy()->addHour(), $start->copy()->addHours(2), User::factory()->create());

        $this->assertSame(BookingStatus::Confirmed, $first->status);
        $this->assertSame(BookingStatus::Confirmed, $second->status);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_buffer_blocks_a_close_following_booking(): void
    {
        $resource = $this->resource(['buffer_minutes' => 30]);
        $start = Carbon::parse('next monday 09:00');

        $this->book($resource, $start, $start->copy()->addHour(), User::factory()->create());

        $this->expectException(BookingConflictException::class);

        $this->book($resource, $start->copy()->addMinutes(75), $start->copy()->addMinutes(105), User::factory()->create());
    }

    public function test_blocked_period_prevents_booking(): void
    {
        $resource = $this->resource();
        $start = Carbon::parse('next monday 10:00');

        ResourceBlockedPeriod::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => $start->copy()->subHour(),
            'ends_at' => $start->copy()->addHour(),
        ]);

        $this->expectException(BookingConflictException::class);

        $this->book($resource, $start, $start->copy()->addMinutes(30), User::factory()->create());
    }

    public function test_cancelled_booking_does_not_block(): void
    {
        $resource = $this->resource();
        $start = Carbon::parse('next monday 11:00');

        $booking = $this->book($resource, $start, $start->copy()->addHour(), User::factory()->create());
        app(BookingService::class)->cancel($booking, $booking->organiser);

        $replacement = $this->book($resource, $start, $start->copy()->addHour(), User::factory()->create());

        $this->assertSame(BookingStatus::Confirmed, $replacement->status);
    }

    public function test_resource_under_maintenance_cannot_be_booked(): void
    {
        $resource = $this->resource(['status' => ResourceStatus::Maintenance]);

        $this->expectException(BookingException::class);

        $this->book($resource, Carbon::parse('next monday 09:00'), Carbon::parse('next monday 10:00'), User::factory()->create());
    }

    public function test_booking_outside_working_hours_is_rejected(): void
    {
        $resource = $this->resource();

        $this->expectException(BookingException::class);

        $this->book($resource, Carbon::parse('next monday 07:00'), Carbon::parse('next monday 07:30'), User::factory()->create());
    }

    public function test_conflict_on_any_resource_in_a_multi_resource_booking_is_rejected(): void
    {
        $room = $this->resource();
        $projector = $this->resource();
        $start = Carbon::parse('next monday 14:00');

        $this->book($room, $start, $start->copy()->addHour(), User::factory()->create());

        $this->expectException(BookingConflictException::class);

        app(BookingService::class)->create(User::factory()->create(), [
            'title' => 'Combined booking',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ], [$room->id, $projector->id]);
    }

    public function test_recurring_booking_detects_conflict_on_a_later_occurrence(): void
    {
        $resource = $this->resource();
        $start = Carbon::parse('next monday 09:00');

        $this->book($resource, $start->copy()->addWeek(), $start->copy()->addWeek()->addHour(), User::factory()->create());

        $this->expectException(BookingConflictException::class);

        app(BookingService::class)->create(User::factory()->create(), [
            'title' => 'Weekly sync',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'recurrence' => ['frequency' => 'weekly', 'interval' => 1, 'count' => 4],
        ], [$resource->id]);
    }

    public function test_recurring_booking_creates_occurrences(): void
    {
        $resource = $this->resource();
        $user = User::factory()->create();
        $start = Carbon::parse('next monday 09:00');

        $parent = app(BookingService::class)->create($user, [
            'title' => 'Daily standup',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'recurrence' => ['frequency' => 'daily', 'interval' => 1, 'count' => 3],
        ], [$resource->id]);

        $this->assertSame(3, Booking::count());
        $this->assertCount(2, $parent->fresh()->occurrences);
    }

    private function resource(array $rules = []): Resource
    {
        $bookingRules = array_merge([
            'min_duration_minutes' => 15,
            'max_duration_minutes' => 480,
            'buffer_minutes' => 0,
        ], array_intersect_key($rules, array_flip(['min_duration_minutes', 'max_duration_minutes', 'buffer_minutes'])));

        $attributes = array_merge([
            'approval_mode' => ApprovalMode::None,
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '08:00',
            'available_to' => '18:00',
        ], array_diff_key($rules, array_flip(['min_duration_minutes', 'max_duration_minutes', 'buffer_minutes'])));

        $attributes['booking_rules'] = $bookingRules;

        return Resource::factory()->create($attributes);
    }

    private function book(Resource $resource, Carbon $start, Carbon $end, User $user): Booking
    {
        return app(BookingService::class)->create($user, [
            'title' => 'Test booking',
            'starts_at' => $start,
            'ends_at' => $end,
        ], [$resource->id]);
    }
}
