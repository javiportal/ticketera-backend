<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tickets = auth()->user()
            ->tickets()
            ->with('ticketType.event', 'attendance')
            ->latest()
            ->paginate(15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse|TicketResource
    {
        $this->authorize('purchase', Ticket::class);

        $ticketType = TicketType::findOrFail($request->ticket_type_id);
        $event = $ticketType->event;

        if ($ticketType->event_id !== (int) $request->event_id) {
            return response()->json(['message' => 'Ticket type does not belong to this event'], 422);
        }

        if (!$event->is_active) {
            return response()->json(['message' => 'Event is not active'], 422);
        }

        if ($ticketType->remainingTickets() <= 0) {
            return response()->json(['message' => 'Sold out for this ticket type'], 422);
        }

        $exists = Ticket::where('user_id', auth()->id())
            ->where('ticket_type_id', $ticketType->id)
            ->whereIn('status', ['valid', 'used'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'You already purchased this ticket type'], 422);
        }

        $ticket = Ticket::create([
            'ticket_type_id' => $ticketType->id,
            'user_id' => auth()->id(),
            'code' => Str::uuid(),
            'status' => 'valid',
        ]);

        $ticket->load('ticketType.event');

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }
}