<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingAttendee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_user_preserves_their_bookings(): void
    {
        $user = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $user->id, 'title' => 'Historic meeting']);
        BookingAttendee::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_organiser' => true,
        ]);

        $user->delete();

        $booking->refresh();

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'title' => 'Historic meeting']);
        $this->assertNull($booking->user_id);

        $attendee = BookingAttendee::where('booking_id', $booking->id)->firstOrFail();
        $this->assertNull($attendee->user_id);
        $this->assertNotEmpty($attendee->name);
    }
}
