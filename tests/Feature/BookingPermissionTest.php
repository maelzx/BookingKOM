<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Models\Booking;
use App\Models\BookingAttendee;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_authenticated_user_can_create_a_booking(): void
    {
        $user = User::factory()->user()->create();

        $this->assertTrue($user->can('create', Booking::class));
    }

    public function test_only_organiser_or_admin_can_update_a_booking(): void
    {
        $organiser = User::factory()->user()->create();
        $other = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();
        $booking = $this->booking($organiser);

        $this->assertTrue($organiser->can('update', $booking));
        $this->assertFalse($other->can('update', $booking));
        $this->assertTrue($admin->can('update', $booking));
    }

    public function test_attendee_can_view_the_booking(): void
    {
        $organiser = User::factory()->user()->create();
        $attendee = User::factory()->user()->create();
        $booking = $this->booking($organiser);

        BookingAttendee::factory()->create([
            'booking_id' => $booking->id,
            'user_id' => $attendee->id,
            'is_organiser' => false,
        ]);

        $this->assertTrue($attendee->can('view', $booking));
    }

    public function test_any_authenticated_user_can_view_a_booking(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $other = User::factory()->user()->create();
        $booking = $this->booking(User::factory()->user()->create(), $manager);

        $this->assertTrue($manager->can('view', $booking));
        $this->assertTrue($other->can('view', $booking));
    }

    public function test_regular_user_cannot_approve_booking(): void
    {
        $booking = $this->booking(User::factory()->user()->create());

        $this->assertFalse(User::factory()->user()->create()->can('approve', $booking));
    }

    public function test_resource_manager_can_only_approve_their_own_resource_bookings(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $mine = $this->booking(User::factory()->user()->create(), $manager);
        $theirs = $this->booking(User::factory()->user()->create());

        $this->assertTrue($manager->can('approve', $mine));
        $this->assertFalse($manager->can('approve', $theirs));
    }

    public function test_regular_user_cannot_manage_resources(): void
    {
        $user = User::factory()->user()->create();

        $this->assertFalse($user->can('create', Resource::class));
    }

    private function booking(User $organiser, ?User $manager = null): Booking
    {
        $booking = Booking::factory()->pending()->create(['user_id' => $organiser->id]);
        $resource = Resource::factory()->create([
            'manager_id' => $manager?->id,
            'approval_mode' => $manager ? ApprovalMode::Owner : ApprovalMode::None,
        ]);

        $booking->resources()->attach($resource->id, ['status' => 'pending']);

        return $booking->fresh();
    }
}
