<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_export_neutralises_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        Booking::factory()->create([
            'user_id' => User::factory()->user()->create()->id,
            'title' => '=1+1',
            'department' => '@SUM(A1)',
        ]);

        $csv = $this->actingAs($admin)->get(route('exports.bookings'))->streamedContent();

        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'@SUM(A1)", $csv);
    }

    public function test_resource_export_neutralises_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        Resource::factory()->create(['name' => '+HYPERLINK("http://evil")']);

        $csv = $this->actingAs($admin)->get(route('exports.resources'))->streamedContent();

        $this->assertStringContainsString("'+HYPERLINK", $csv);
    }

    public function test_utilisation_export_neutralises_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        Resource::factory()->create(['name' => '-2+3']);

        $csv = $this->actingAs($admin)->get(route('exports.utilisation'))->streamedContent();

        $this->assertStringContainsString("'-2+3", $csv);
    }
}
