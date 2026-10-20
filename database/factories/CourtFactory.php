<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Pickleball Court 1', 'Pickleball Court 2',
                'Pickleball Court 3', 'Pickleball Court 4',
                'Pickleball Court 5', 'Pickleball Court 6',
            ]).' '.fake()->unique()->numberBetween(1, 9999),
            'location' => 'Main Facility, Barangay San Jose',
            'description' => fake()->sentence(),
            'hourly_rate' => fake()->randomFloat(2, 100, 800),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
