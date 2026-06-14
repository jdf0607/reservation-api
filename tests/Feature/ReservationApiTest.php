<?php

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lista las reservas con la estructura JSON correcta', function () {
    $user = User::factory()->create();
    Reservation::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson('/api/reservations');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'guest_name', 'guest_email', 'property_name',
                        'check_in', 'check_out', 'status', 'amount'],
            ],
        ]);
});

it('crea una reserva y devuelve 201', function () {
    $user = User::factory()->create();

    $payload = [
        'guest_name' => 'Ana López',
        'guest_email' => 'ana@example.com',
        'property_name' => 'Villa Costa',
        'check_in' => now()->addDays(5)->toDateString(),
        'check_out' => now()->addDays(10)->toDateString(),
        'amount' => 500,
    ];

    $response = $this->actingAs($user)->postJson('/api/reservations', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.guest_name', 'Ana López')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('reservations', [
        'guest_email' => 'ana@example.com',
        'user_id' => $user->id,
    ]);
});

it('rechaza una reserva con fecha de salida anterior a la de entrada', function () {
    $user = User::factory()->create();

    $payload = [
        'guest_name' => 'Test',
        'guest_email' => 'test@example.com',
        'property_name' => 'X',
        'check_in' => now()->addDays(10)->toDateString(),
        'check_out' => now()->addDays(5)->toDateString(),
        'amount' => 100,
    ];

    $response = $this->actingAs($user)->postJson('/api/reservations', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['check_out']);
});

it('impide ver una reserva de otro usuario (403)', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $reservation = Reservation::factory()->create(['user_id' => $owner->id]);

    $response = $this->actingAs($intruder)->getJson("/api/reservations/{$reservation->id}");

    $response->assertForbidden(); // 403
});