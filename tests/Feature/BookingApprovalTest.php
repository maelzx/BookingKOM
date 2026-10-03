<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use App\Notifications\BookingDecision;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_booking_without_approval_is_auto_confirmed(): void
    {
        $resource = $this->resource(ApprovalMode::None);
        $booking = $this->create($resource, User::factory()->create());

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('not_required', $booking->resources->first()->pivot->status);
    }

    public function test_booking_requiring_approval_starts_pending(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource(ApprovalMode::Owner, $manager);
        $booking = $this->create($resource, User::factory()->create());

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('pending', $booking->resources->first()->pivot->status);
    }

    public function test_owner_approval_confirms_the_booking(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource(ApprovalMode::Owner, $manager);
        $booking = $this->create($resource, User::factory()->create());

        app(BookingService::class)->approve($booking, $manager);

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('approved', $booking->resources->first()->pivot->status);
        $this->assertSame($manager->id, $booking->approved_by);
    }

    public function test_rejection_marks_booking_rejected(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource(ApprovalMode::Owner, $manager);
        $booking = $this->create($resource, User::factory()->create());

        app(BookingService::class)->reject($booking, $manager, 'Room unavailable');

        $booking->refresh();
        $this->assertSame(BookingStatus::Rejected, $booking->status);
        $this->assertSame('rejected', $booking->resources->first()->pivot->status);
        $this->assertSame('Room unavailable', $booking->decision_note);
    }

    public function test_partial_approval_keeps_booking_pending(): void
    {
        $managerA = User::factory()->resourceManager()->create();
        $managerB = User::factory()->resourceManager()->create();

        $room = $this->resource(ApprovalMode::Owner, $managerA);
        $projector = $this->resource(ApprovalMode::Owner, $managerB);

        $booking = app(BookingService::class)->create(User::factory()->create(), [
            'title' => 'Needs two approvals',
            'starts_at' => $start = Carbon::parse('next tuesday 09:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$room->id, $projector->id]);

        app(BookingService::class)->approve($booking, $managerA);

        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);

        app(BookingService::class)->approve($booking->fresh(), $managerB);

        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_admin_approval_mode_requires_admin_or_manager(): void
    {
        $admin = User::factory()->admin()->create();
        $resource = $this->resource(ApprovalMode::Admin);
        $booking = $this->create($resource, User::factory()->create());

        $this->assertTrue($admin->can('approve', $booking));
        $this->assertFalse(User::factory()->user()->create()->can('approve', $booking));

        app(BookingService::class)->approve($booking, $admin);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_partial_line_approval_does_not_notify_the_organiser(): void
    {
        $managerA = User::factory()->resourceManager()->create();
        $managerB = User::factory()->resourceManager()->create();

        $room = $this->resource(ApprovalMode::Owner, $managerA);
        $projector = $this->resource(ApprovalMode::Owner, $managerB);
        $organiser = User::factory()->user()->create();

        $booking = app(BookingService::class)->create($organiser, [
            'title' => 'Needs two approvals',
            'starts_at' => $start = Carbon::parse('next tuesday 10:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$room->id, $projector->id]);

        app(BookingService::class)->approve($booking, $managerA);

        Notification::assertNotSentTo($organiser, BookingDecision::class);
    }

    public function test_manager_cannot_reject_a_line_they_do_not_own(): void
    {
        $managerA = User::factory()->resourceManager()->create();
        $managerB = User::factory()->resourceManager()->create();

        $room = $this->resource(ApprovalMode::Owner, $managerA);
        $projector = $this->resource(ApprovalMode::Owner, $managerB);

        $booking = app(BookingService::class)->create(User::factory()->user()->create(), [
            'title' => 'Shared',
            'starts_at' => $start = Carbon::parse('next tuesday 11:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$room->id, $projector->id]);

        $this->expectException(BookingException::class);

        app(BookingService::class)->reject($booking, $managerA, 'not mine', $projector->id);
    }

    public function test_manager_rejecting_their_own_line_rejects_the_booking(): void
    {
        $managerA = User::factory()->resourceManager()->create();
        $managerB = User::factory()->resourceManager()->create();

        $room = $this->resource(ApprovalMode::Owner, $managerA);
        $projector = $this->resource(ApprovalMode::Owner, $managerB);

        $booking = app(BookingService::class)->create(User::factory()->user()->create(), [
            'title' => 'Shared',
            'starts_at' => $start = Carbon::parse('next tuesday 12:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$room->id, $projector->id]);

        app(BookingService::class)->reject($booking, $managerA, 'room unavailable', $room->id);

        $this->assertSame(BookingStatus::Rejected, $booking->fresh()->status);
    }

    public function test_approving_with_nothing_pending_throws(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource(ApprovalMode::Owner, $manager);
        $booking = $this->create($resource, User::factory()->user()->create());

        $service = app(BookingService::class);
        $service->approve($booking, $manager);

        $this->expectException(BookingException::class);

        $service->approve($booking->fresh(), $manager);
    }

    public function test_editing_a_confirmed_booking_keeps_approved_lines(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource(ApprovalMode::Owner, $manager);
        $organiser = User::factory()->user()->create();

        $service = app(BookingService::class);
        $booking = $service->create($organiser, [
            'title' => 'Original',
            'starts_at' => $start = Carbon::parse('next wednesday 09:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$resource->id]);

        $service->approve($booking, $manager);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);

        $service->update($booking->fresh(), [
            'title' => 'Renamed',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ], [$resource->id]);

        $booking->refresh();

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('approved', $booking->resources->first()->pivot->status);
    }

    public function test_adding_an_approval_required_resource_resets_to_pending(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $open = $this->resource(ApprovalMode::None);
        $strict = $this->resource(ApprovalMode::Owner, $manager);
        $organiser = User::factory()->user()->create();

        $service = app(BookingService::class);
        $booking = $service->create($organiser, [
            'title' => 'Grows',
            'starts_at' => $start = Carbon::parse('next wednesday 14:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$open->id]);

        $this->assertSame(BookingStatus::Confirmed, $booking->status);

        $service->update($booking, [
            'title' => 'Grows',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
        ], [$open->id, $strict->id]);

        $booking->refresh();

        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('pending', $booking->resources->firstWhere('id', $strict->id)->pivot->status);
        $this->assertSame('not_required', $booking->resources->firstWhere('id', $open->id)->pivot->status);
    }

    private function resource(ApprovalMode $mode, ?User $manager = null): Resource
    {
        return Resource::factory()->create([
            'approval_mode' => $mode,
            'manager_id' => $manager?->id,
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '08:00',
            'available_to' => '18:00',
        ]);
    }

    private function create(Resource $resource, User $user): Booking
    {
        return app(BookingService::class)->create($user, [
            'title' => 'Approval test',
            'starts_at' => $start = Carbon::parse('next wednesday 10:00'),
            'ends_at' => $start->copy()->addHour(),
        ], [$resource->id]);
    }
}
