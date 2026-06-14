<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * ¿Puede el usuario ver esta reserva?
     */
    public function view(User $user, Reservation $reservation): bool
    {
        return $this->owns($user, $reservation);
    }

    /**
     * ¿Puede el usuario modificar el estado de esta reserva?
     */
    public function update(User $user, Reservation $reservation): bool
    {
        return $this->owns($user, $reservation);
    }

    /**
     * ¿Puede el usuario cancelar esta reserva?
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $this->owns($user, $reservation);
    }

    /**
     * Un usuario "posee" una reserva si es su dueño.
     * Las reservas sin dueño (user_id null, del seeder antiguo) no son de nadie.
     */
    private function owns(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
}