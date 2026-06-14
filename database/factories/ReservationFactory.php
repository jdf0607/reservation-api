<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReservationFactory extends Factory
{
    public function definition(): array
    {
        // Fecha de entrada en los próximos 60 días
        $checkIn = Carbon::today()->addDays($this->faker->numberBetween(1, 60));
        // Estancia de 1 a 14 noches
        $checkOut = (clone $checkIn)->addDays($this->faker->numberBetween(1, 14));

        return [
            'guest_name' => $this->faker->name(),
            'guest_email' => $this->faker->safeEmail(),
            'property_name' => $this->faker->randomElement([
                'Apartamento Centro', 'Villa Costa', 'Loft Moderno',
                'Casa Rural El Roble', 'Ático Vista Mar',
            ]),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $this->faker->randomElement(ReservationStatus::cases()),
            'amount' => $this->faker->randomFloat(2, 50, 2000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    // Estados "states" para crear reservas con un estado concreto en los tests
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