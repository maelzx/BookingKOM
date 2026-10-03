<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_manager_can_upload_an_attachment_to_their_resource(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = Resource::factory()->create(['manager_id' => $manager->id]);

        $this->actingAs($manager);

        Volt::test('resources.show', ['resource' => $resource])
            ->set('attachment', UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'))
            ->call('uploadAttachment')
            ->assertHasNoErrors();

        $attachment = Attachment::firstOrFail();

        $this->assertSame($resource->id, $attachment->attachable_id);
        $this->assertSame('manual.pdf', $attachment->original_name);
        $this->assertSame($manager->id, $attachment->uploaded_by);
        Storage::disk('local')->assertExists($attachment->file_path);
    }

    public function test_regular_user_cannot_upload_an_attachment(): void
    {
        $user = User::factory()->user()->create();
        $resource = Resource::factory()->create();

        $this->actingAs($user);

        $this->assertFalse($user->can('update', $resource));

        $this->expectException(AuthorizationException::class);

        Gate::authorize('update', $resource);
    }

    public function test_attachment_view_is_restricted_to_uploader_manager_and_admin(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $uploader = User::factory()->user()->create();
        $other = User::factory()->user()->create();
        $admin = User::factory()->admin()->create();

        $resource = Resource::factory()->create(['manager_id' => $manager->id]);
        $attachment = $this->attachment($resource, $uploader);

        $this->assertTrue($manager->can('view', $attachment->load('attachable')));
        $this->assertTrue($uploader->can('view', $attachment));
        $this->assertTrue($admin->can('view', $attachment));
        $this->assertFalse($other->can('view', $attachment->fresh()));
    }

    public function test_attachment_download_is_authorized(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $other = User::factory()->user()->create();
        $resource = Resource::factory()->create(['manager_id' => $manager->id]);
        $attachment = $this->attachment($resource, $manager);

        $this->actingAs($other)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_manager_can_delete_an_attachment(): void
    {
        $manager = User::factory()->resourceManager()->create();
        $resource = Resource::factory()->create(['manager_id' => $manager->id]);
        $attachment = $this->attachment($resource, $manager);

        Storage::disk('local')->assertExists($attachment->file_path);

        $this->actingAs($manager);

        Volt::test('resources.show', ['resource' => $resource])
            ->call('deleteAttachment', $attachment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->file_path);
    }

    private function attachment(Resource $resource, User $uploader): Attachment
    {
        $file = UploadedFile::fake()->create('manual.pdf', 50, 'application/pdf');
        $path = $file->store('attachments', 'local');

        return $resource->attachments()->create([
            'original_name' => 'manual.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 50,
            'uploaded_by' => $uploader->id,
        ]);
    }
}
