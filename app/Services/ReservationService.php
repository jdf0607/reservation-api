<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Events\ReservationConfirmed;
use App\Models\Reservation;
use App\Models\ReservationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Crea una reserva y registra su evento de creación.
     */
    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = ReservationStatus::Pending;
        
            $reservation = Reservation::create($data);

            $this->logEvent($reservation, 'created',
                "Reserva creada para {$reservation->guest_name}.");

            return $reservation;
        });
    }

    /**
     * Cambia el estado de una reserva, validando que la transición sea legal.
     */
    public function changeStatus(Reservation $reservation, ReservationStatus $newStatus): Reservation
    {
        $current = $reservation->status;

        if (! $current->canTransitionTo($newStatus)) {
            throw ValidationException::withMessages([
                'status' => "No se puede cambiar de '{$current->value}' a '{$newStatus->value}'.",
            ]);
        }

        return DB::transaction(function () use ($reservation, $newStatus) {
    $reservation->update(['status' => $newStatus]);

    $this->logEvent($reservation, $newStatus->value,
        "El estado cambió a '{$newStatus->value}'.");

        // Si se confirma, anunciamos el hecho. Quién reaccione es cosa de los listeners.
        if ($newStatus === ReservationStatus::Confirmed) {
            ReservationConfirmed::dispatch($reservation);
        }

        return $reservation->refresh();
    });
    }

    /**
     * Cancela una reserva (atajo sobre changeStatus).
     */
    public function cancel(Reservation $reservation): Reservation
    {
        return $this->changeStatus($reservation, ReservationStatus::Cancelled);
    }

    /**
     * Registra un evento en el historial de la reserva.
     */
    private function logEvent(Reservation $reservation, string $type, string $description): ReservationEvent
    {
        return $reservation->events()->create([
            'type' => $type,
            'description' => $description,
        ]);
    }
}