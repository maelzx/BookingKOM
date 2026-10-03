<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Resource;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SettingEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_max_bookings_per_day_is_enforced(): void
    {
        $resource = $this->resource(['max_bookings_per_day' => 1]);
        $start = Carbon::parse('next monday 09:00');

        $this->service()->create(User::factory()->create(), [
            'title' => 'First',
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
        ], [$resource->id]);

        $this->expectException(BookingException::class);

        $this->service()->create(User::factory()->create(), [
            'title' => 'Second',
            'starts_at' => $start->copy()->addHour(),
            'ends_at' => $start->copy()->addMinutes(90),
        ], [$resource->id]);
    }

    public function test_cancellation_notice_blocks_late_cancellation_for_users(): void
    {
        Setting::set('cancellation_notice_hours', 2, 'integer');
        Setting::flushCache();

        $resource = $this->resource();
        $user = User::factory()->user()->create();
        $booking = $this->service()->create($user, [
            'title' => 'Soon',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
        ], [$resource->id]);

        $this->expectException(BookingException::class);

        $this->service()->cancel($booking, $user);
    }

    public function test_admin_can_cancel_inside_the_notice_window(): void
    {
        Setting::set('cancellation_notice_hours', 2, 'integer');
        Setting::flushCache();

        $resource = $this->resource();
        $user = User::factory()->user()->create();
        $booking = $this->service()->create($user, [
            'title' => 'Soon',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
        ], [$resource->id]);

        $this->service()->cancel($booking, User::factory()->admin()->create());

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_pending_bookings_are_always_cancellable(): void
    {
        Setting::set('cancellation_notice_hours', 2, 'integer');
        Setting::flushCache();

        $manager = User::factory()->resourceManager()->create();
        $resource = $this->resource([], ApprovalMode::Owner, $manager);
        $user = User::factory()->user()->create();

        $booking = $this->service()->create($user, [
            'title' => 'Awaiting approval',
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
        ], [$resource->id]);

        $this->assertSame(BookingStatus::Pending, $booking->status);

        $this->service()->cancel($booking, $user);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_organisation_timezone_setting_is_applied(): void
    {
        Setting::set('org_timezone', 'Asia/Tokyo');
        Setting::flushCache();

        app()->getProvider(AppServiceProvider::class)->boot();

        $this->assertSame('Asia/Tokyo', config('app.timezone'));
    }

    private function service(): BookingService
    {
        return app(BookingService::class);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function resource(array $rules = [], ApprovalMode $mode = ApprovalMode::None, ?User $manager = null): Resource
    {
        return Resource::factory()->create([
            'approval_mode' => $mode,
            'manager_id' => $manager?->id,
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '00:00',
            'available_to' => '23:59',
            'booking_rules' => array_merge([
                'min_duration_minutes' => 15,
                'max_duration_minutes' => 480,
                'buffer_minutes' => 0,
            ], $rules),
        ]);
    }
}
