<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        $checkIn = Carbon::today()->addDays(fake()->numberBetween(1, 60));
        $checkOut = (clone $checkIn)->addDays(fake()->numberBetween(1, 14));

        return [
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'property_name' => fake()->randomElement([
                'Apartamento Centro', 'Villa Costa', 'Loft Moderno',
                'Casa Rural El Roble', 'Ático Vista Mar',
            ]),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => fake()->randomElement(ReservationStatus::cases()),
            'amount' => fake()->randomFloat(2, 50, 2000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ReservationStatus::Pending]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => ReservationStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => ReservationStatus::Cancelled]);
    }
}