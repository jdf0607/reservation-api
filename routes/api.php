<?php

use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('reservations', ReservationController::class);

// Ruta extra para modificar solo el estado
Route::patch('reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])
    ->name('reservations.update-status');