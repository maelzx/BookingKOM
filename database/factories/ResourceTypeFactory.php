<?php

namespace Database\Factories;

use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ResourceType>
 */
class ResourceTypeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Meeting Room', 'Company Vehicle', 'Projector', 'Laptop Cart',
            'Training Room', 'Camera', 'Audio Kit', 'Shared Desk',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->sentence(),
            'requires_approval' => fake()->boolean(30),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
