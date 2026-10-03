<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+14 days');
        $start->setTime(fake()->numberBetween(8, 16), fake()->randomElement([0, 30]));

        return [
            'title' => fake()->sentence(3),
            'purpose' => fake()->optional()->paragraph(),
            'user_id' => User::factory(),
            'department' => fake()->randomElement(['Operations', 'Sales', 'IT', 'Finance', 'HR']),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+1 hour'),
            'status' => BookingStatus::Confirmed,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Pending,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::NoShow,
            'no_show_at' => now(),
        ]);
    }
}
