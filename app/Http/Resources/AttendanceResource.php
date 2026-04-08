<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'checked_in_at' => $this->checked_in_at,
        ];
    }
}