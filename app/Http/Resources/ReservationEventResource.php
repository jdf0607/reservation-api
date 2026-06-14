<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_name' => $this->guest_name,
            'guest_email' => $this->guest_email,
            'property_name' => $this->property_name,
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'status' => $this->status->value,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Los eventos solo se incluyen si se han cargado (evita queries innecesarias)
            'events' => ReservationEventResource::collection(
                $this->whenLoaded('events')
            ),
        ];
    }
}