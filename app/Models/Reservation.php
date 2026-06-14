<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_name',
        'guest_email',
        'property_name',
        'check_in',
        'check_out',
        'status',
        'amount',
        'notes',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'status' => ReservationStatus::class,
        'amount' => 'decimal:2',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(ReservationEvent::class);
    }
}