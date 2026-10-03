<?php

namespace Database\Factories;

use App\Enums\ApprovalMode;
use App\Enums\ResourceStatus;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_type_id' => ResourceType::factory(),
            'name' => ucfirst(fake()->words(2, true)),
            'code' => strtoupper(fake()->unique()->bothify('RES-####')),
            'description' => fake()->sentence(),
            'location' => fake()->randomElement(['HQ — Level 1', 'HQ — Level 2', 'Branch A', 'Warehouse']),
            'status' => ResourceStatus::Active,
            'capacity' => fake()->optional()->numberBetween(2, 40),
            'approval_mode' => ApprovalMode::None,
            'manager_id' => User::factory()->resourceManager(),
            'booking_rules' => [
                'min_duration_minutes' => 15,
                'max_duration_minutes' => 480,
                'buffer_minutes' => 0,
            ],
            'available_days' => [1, 2, 3, 4, 5],
            'is_bookable' => true,
        ];
    }

    public function requiringApproval(ApprovalMode $mode = ApprovalMode::Admin): static
    {
        return $this->state(fn (array $attributes) => [
            'approval_mode' => $mode,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ResourceStatus::Maintenance,
        ]);
    }
}
