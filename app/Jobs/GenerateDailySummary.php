<?php

namespace App\Jobs;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateDailySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        // Contamos las reservas creadas hoy, agrupadas por estado.
        $summary = [
            'date' => $today->toDateString(),
            'total' => Reservation::whereDate('created_at', $today)->count(),
            'pending' => Reservation::whereDate('created_at', $today)
                ->where('status', ReservationStatus::Pending)->count(),
            'confirmed' => Reservation::whereDate('created_at', $today)
                ->where('status', ReservationStatus::Confirmed)->count(),
            'cancelled' => Reservation::whereDate('created_at', $today)
                ->where('status', ReservationStatus::Cancelled)->count(),
        ];

        // "Generamos" el resumen registrándolo en el log.
        Log::info('Resumen diario de reservas generado', $summary);
    }
}