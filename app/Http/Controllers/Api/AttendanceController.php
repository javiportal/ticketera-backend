<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function validateTicket(string $code): JsonResponse|AttendanceResource
    {
        Gate::authorize('validate-tickets');

        $ticket = Ticket::where('code', $code)->first();

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found'], 404);
        }

        if ($ticket->status === 'cancelled') {
            return response()->json(['message' => 'Ticket has been cancelled'], 422);
        }

        if ($ticket->status === 'used') {
            return response()->json(['message' => 'Ticket already used'], 422);
        }

        $ticket->update(['status' => 'used']);

        $attendance = Attendance::create([
            'ticket_id' => $ticket->id,
            'checked_in_at' => now(),
        ]);

        $attendance->load('ticket.ticketType.event', 'ticket.user');

        return new AttendanceResource($attendance);
    }
}