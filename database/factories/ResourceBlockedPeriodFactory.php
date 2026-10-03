<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\Resource;
use App\Models\ResourceBlockedPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceBlockedPeriod>
 */
class ResourceBlockedPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+1 month');

        return [
            'resource_id' => Resource::factory(),
            'type' => BlockType::Blocked,
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+2 hours'),
            'reason' => fake()->sentence(4),
        ];
    }
}
