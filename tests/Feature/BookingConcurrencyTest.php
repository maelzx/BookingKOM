<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Exceptions\BookingConflictException;
use App\Exceptions\BookingException;
use App\Models\Resource;
use App\Models\User;
use App\Services\BookingAvailability;
use App\Services\BookingService;
use App\Services\BookingStatusTransition;
use App\Services\RecurrenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_creation_is_blocked_while_the_resource_lock_is_held(): void
    {
        $resource = $this->resource();
        $user = User::factory()->create();

        $lock = Cache::lock('booking-resource-'.$resource->id, 10);
        $this->assertTrue($lock->get());

        try {
            // A zero-second wait proves the repository routes through the lock.
            $service = new BookingService(
                app(BookingAvailability::class),
                app(BookingStatusTransition::class),
                app(RecurrenceService::class),
                0,
            );

            $this->expectException(BookingException::class);

            $service->create($user, [
                'title' => 'Should block',
                'starts_at' => $start = Carbon::parse('next monday 09:00'),
                'ends_at' => $start->copy()->addHour(),
            ], [$resource->id]);
        } finally {
            $lock->release();
        }
    }

    public function test_resource_lock_is_released_after_a_successful_booking(): void
    {
        $resource = $this->resource();
        $user = User::factory()->create();

        app(BookingService::class)->create($user, [
            'title' => 'First booking',
            'starts_at' => $start = Carbon::parse('next monday 09:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$resource->id]);

        $lock = Cache::lock('booking-resource-'.$resource->id, 10);
        $this->assertTrue($lock->get(), 'Lock should be released after the transaction commits.');
        $lock->release();
    }

    public function test_second_overlapping_booking_is_rejected(): void
    {
        $resource = $this->resource();
        $start = Carbon::parse('next monday 09:00');

        app(BookingService::class)->create(User::factory()->create(), [
            'title' => 'First',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ], [$resource->id]);

        $this->expectException(BookingConflictException::class);

        app(BookingService::class)->create(User::factory()->create(), [
            'title' => 'Second',
            'starts_at' => $start->copy()->addMinutes(30),
            'ends_at' => $start->copy()->addMinutes(90),
        ], [$resource->id]);
    }

    private function resource(): Resource
    {
        return Resource::factory()->create([
            'approval_mode' => ApprovalMode::None,
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '08:00',
            'available_to' => '18:00',
        ]);
    }
}
