<?php

use App\Enums\ReservationStatus;

it('permite transicionar de pending a confirmed o cancelled', function () {
    expect(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Confirmed))->toBeTrue();
    expect(ReservationStatus::Pending->canTransitionTo(ReservationStatus::Cancelled))->toBeTrue();
});

it('no permite transicionar desde un estado cancelado', function () {
    expect(ReservationStatus::Cancelled->canTransitionTo(ReservationStatus::Confirmed))->toBeFalse();
    expect(ReservationStatus::Cancelled->canTransitionTo(ReservationStatus::Pending))->toBeFalse();
});