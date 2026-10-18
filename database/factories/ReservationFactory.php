<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            'reference_number' => 'RSRV-'.fake()->unique()->numerify('########'),
            'user_id' => User::factory(),
            'court_id' => Court::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'hourly_rate' => 500,
            'total_amount' => 1000,
            'status' => ReservationStatus::Pending,
            'payment_method' => PaymentMethod::Online,
            'payment_deadline' => now()->addMinutes(15),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (): array => ['status' => ReservationStatus::Confirmed]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => ReservationStatus::Expired,
            'payment_deadline' => now()->subMinute(),
        ]);
    }
}
