<?php

namespace Database\Seeders;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // 30 reservas con estados variados (la factory ya reparte aleatoriamente)
        Reservation::factory()
            ->count(30)
            ->create()
            ->each(function (Reservation $reservation) {
                // Toda reserva nace con un evento de creación
                ReservationEvent::create([
                    'reservation_id' => $reservation->id,
                    'type' => 'created',
                    'description' => "Reserva creada para {$reservation->guest_name}.",
                ]);

                // Si está confirmada o cancelada, añadimos el evento correspondiente
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