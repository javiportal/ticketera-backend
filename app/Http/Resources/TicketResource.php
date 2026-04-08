<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'ticket_type' => new TicketTypeResource($this->whenLoaded('ticketType')),
            'event' => new EventResource($this->whenLoaded('ticketType.event')),
            'user' => new UserResource($this->whenLoaded('user')),
            'attendance' => new AttendanceResource($this->whenLoaded('attendance')),
            'created_at' => $this->created_at,
        ];
    }
}