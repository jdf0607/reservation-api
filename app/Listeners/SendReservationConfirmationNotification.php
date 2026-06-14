<?php

namespace App\Listeners;

use App\Events\ReservationConfirmed;
use Illuminate\Support\Facades\Log;

class SendReservationConfirmationNotification
{
    public function handle(ReservationConfirmed $event): void
    {
        $reservation = $event->reservation;

        // Simulamos el envío de una notificación al huésped escribiendo en el log.
        Log::info('Notificación de confirmación enviada', [
            'reservation_id' => $reservation->id,
            'guest_email' => $reservation->guest_email,
            'property' => $reservation->property_name,
        ]);
    }
}