<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ResourceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_open_resource_create(): void
    {
        $this->actingAs(User::factory()->user()->create())
            ->get('/resources/create')
            ->assertForbidden();
    }

    public function test_resource_manager_can_create_a_resource(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $type = ResourceType::factory()->create();

        $this->actingAs($manager);

        Volt::test('resources.form')
            ->set('name', 'Meeting Room 9')
            ->set('resource_type_id', $type->id)
            ->set('status', 'active')
            ->set('approval_mode', 'none')
            ->set('available_days', [1, 2, 3, 4, 5])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('resources', [
            'name' => 'Meeting Room 9',
            'created_by' => $manager->id,
        ]);
    }

    public function test_resource_manager_can_only_edit_their_own_resource(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $mine = Resource::factory()->create(['manager_id' => $manager->id]);
        $theirs = Resource::factory()->create();

        $this->assertTrue($manager->can('update', $mine));
        $this->assertFalse($manager->can('update', $theirs));
    }

    public function test_only_admin_can_delete_a_resource(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $admin = User::factory()->admin()->create();
        $resource = Resource::factory()->create(['manager_id' => $manager->id]);

        $this->assertFalse($manager->can('delete', $resource));
        $this->assertTrue($admin->can('delete', $resource));
    }

    public function test_resource_index_lists_resources_for_any_user(): void
    {
        Resource::factory()->create(['name' => 'Visible Room']);

        $this->actingAs(User::factory()->user()->create())
            ->get('/resources')
            ->assertOk()
            ->assertSee('Visible Room');
    }
}
