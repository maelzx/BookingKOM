<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reminder_is_sent_only_once_per_booking(): void
    {
        Notification::fake();

        $organiser = User::factory()->create();
        $booking = $this->upcomingBooking($organiser);

        $this->artisan('bookings:remind')->assertSuccessful();

        Notification::assertSentToTimes($organiser, BookingReminder::class, 1);
        $this->assertNotNull($booking->fresh()->reminded_at);

        // A second scheduled run must not duplicate the reminder.
        $this->artisan('bookings:remind')->assertSuccessful();

        Notification::assertSentToTimes($organiser, BookingReminder::class, 1);
    }

    public function test_force_flag_resends_the_reminder(): void
    {
        Notification::fake();

        $organiser = User::factory()->create();
        $this->upcomingBooking($organiser);

        $this->artisan('bookings:remind')->assertSuccessful();
        $this->artisan('bookings:remind', ['--force' => true])->assertSuccessful();

        Notification::assertSentToTimes($organiser, BookingReminder::class, 2);
    }

    public function test_bookings_outside_the_lead_window_are_not_reminded(): void
    {
        Notification::fake();

        $organiser = User::factory()->create();
        $this->upcomingBooking($organiser, now()->addHours(5));

        $this->artisan('bookings:remind')->assertSuccessful();

        Notification::assertNothingSent();
    }

    private function upcomingBooking(User $organiser, ?Carbon $startsAt = null): Booking
    {
        $startsAt ??= now()->addMinutes(30);

        return Booking::factory()->create([
            'user_id' => $organiser->id,
            'status' => BookingStatus::Confirmed,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
        ]);
    }
}
