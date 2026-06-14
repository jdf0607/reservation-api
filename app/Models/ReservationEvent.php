<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationEvent extends Model
{
    use HasFactory;

    // Evento inmutable: solo created_at, sin updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'reservation_id',
        'type',
        'description',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}