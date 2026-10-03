<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ResourceQrCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_qr_code_is_cached(): void
    {
        $resource = Resource::factory()->create();

        Cache::spy();

        $this->actingAs(User::factory()->user()->create())
            ->get('/resources/'.$resource->id)
            ->assertOk();

        Cache::shouldHaveReceived('remember')
            ->withArgs(fn ($key): bool => $key === 'resource-qr:'.$resource->id)
            ->once();
    }
}
