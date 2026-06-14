<?php

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lista las reservas con la estructura JSON correcta', function () {
    Reservation::factory()->count(3)->create();

    $response = $this->getJson('/api/reservations');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'guest_name', 'guest_email', 'property_name',
                        'check_in', 'check_out', 'status', 'amount'],
            ],
        ]);
});

it('crea una reserva y devuelve 201', function () {
    $payload = [
        'guest_name' => 'Ana López',
        'guest_email' => 'ana@example.com',
        'property_name' => 'Villa Costa',
        'check_in' => now()->addDays(5)->toDateString(),
        'check_out' => now()->addDays(10)->toDateString(),
        'amount' => 500,
    ];

    $response = $this->postJson('/api/reservations', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.guest_name', 'Ana López')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('reservations', ['guest_email' => 'ana@example.com']);
});

it('rechaza una reserva con fecha de salida anterior a la de entrada', function () {
    $payload = [
        'guest_name' => 'Test',
        'guest_email' => 'test@example.com',
        'property_name' => 'X',
        'check_in' => now()->addDays(10)->toDateString(),
        'check_out' => now()->addDays(5)->toDateString(),
        'amount' => 100,
    ];

    $response = $this->postJson('/api/reservations', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['check_out']);
});