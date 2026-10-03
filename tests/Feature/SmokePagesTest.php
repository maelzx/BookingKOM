<?php

namespace Tests\Feature;

use App\Enums\ApprovalMode;
use App\Models\Booking;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;
use Tests\TestCase;

class SmokePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_every_main_page(): void
    {
        $admin = User::factory()->admin()->create();
        $type = ResourceType::factory()->create();
        $resource = Resource::factory()->create([
            'resource_type_id' => $type->id,
            'manager_id' => $admin->id,
            'approval_mode' => ApprovalMode::Owner,
        ]);

        $booking = Booking::factory()->pending()->create(['user_id' => $admin->id]);
        $booking->resources()->attach($resource->id, ['status' => 'pending']);

        $this->actingAs($admin);

        foreach ([
            '/dashboard',
            '/calendar',
            '/resources',
            '/resources/create',
            '/resources/'.$resource->id,
            '/resources/'.$resource->id.'/edit',
            '/bookings',
            '/bookings/create',
            '/bookings/'.$booking->id,
            '/bookings/'.$booking->id.'/edit',
            '/approvals',
            '/reports',
            '/notifications',
            '/resource-types',
            '/users',
            '/settings',
            '/profile',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_regular_user_pages_render(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user);

        foreach (['/dashboard', '/calendar', '/resources', '/bookings', '/bookings/create', '/notifications', '/profile'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_public_qr_scan_page_renders(): void
    {
        $resource = Resource::factory()->create(['code' => 'RES-9001']);

        $this->get('/r/RES-9001')
            ->assertOk()
            ->assertSee($resource->name);
    }

    public function test_regular_user_cannot_access_admin_pages(): void
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->get('/users')->assertForbidden();
        $this->get('/settings')->assertForbidden();
        $this->get('/resource-types')->assertForbidden();
        $this->get('/approvals')->assertForbidden();
        $this->get('/reports')->assertForbidden();
    }

    public function test_booking_can_be_created_via_the_component(): void
    {
        $user = User::factory()->user()->create();
        $resource = Resource::factory()->create([
            'available_days' => [1, 2, 3, 4, 5, 6, 7],
            'available_from' => '08:00',
            'available_to' => '18:00',
            'approval_mode' => ApprovalMode::None,
        ]);

        $this->actingAs($user);

        Volt::test('bookings.form')
            ->set('title', 'Component booking')
            ->set('date', Carbon::parse('next monday')->toDateString())
            ->set('startTime', '09:00')
            ->set('endTime', '10:00')
            ->set('resourceIds', [$resource->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('bookings', ['title' => 'Component booking', 'user_id' => $user->id]);
    }
}
