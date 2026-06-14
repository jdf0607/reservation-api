<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;

// Login: público, devuelve el token
Route::post('login', [AuthController::class, 'login']);

// Rutas protegidas: requieren token válido (auth:sanctum)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::apiResource('reservations', ReservationController::class);

    Route::patch('reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])
        ->name('reservations.update-status');
});