<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // Asignamos todas las reservas al usuario admin (creado en DatabaseSeeder)
        $admin = User::first();

        Reservation::factory()
            ->count(30)
            ->create(['user_id' => $admin->id])
            ->each(function (Reservation $reservation) {
                ReservationEvent::create([
                    'reservation_id' => $reservation->id,
                    'type' => 'created',
                    'description' => "Reserva creada para {$reservation->guest_name}.",
                ]);

                if ($reservation->status === ReservationStatus::Confirmed) {
                    ReservationEvent::create([
                        'reservation_id' => $reservation->id,
                        'type' => 'confirmed',
                        'description' => 'La reserva ha sido confirmada.',
                    ]);
                }

                if ($reservation->status === ReservationStatus::Cancelled) {
                    ReservationEvent::create([
                        'reservation_id' => $reservation->id,
                        'type' => 'cancelled',
                        'description' => 'La reserva ha sido cancelada.',
                    ]);
                }
            });
    }
}