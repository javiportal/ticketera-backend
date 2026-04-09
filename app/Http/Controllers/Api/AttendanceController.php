<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class AttendanceController extends Controller
{
    #[OA\Post(
        path: '/tickets/{code}/validate',
        summary: 'Validar entrada en puerta',
        description: 'Valida una entrada por su código UUID. Marca el ticket como "used" y registra la asistencia. Requiere permiso "validate-tickets".',
        tags: ['Attendance'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, description: 'Código UUID de la entrada', schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada validada exitosamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/AttendanceResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Ticket not found'),
            ])),
            new OA\Response(response: 422, description: 'Entrada cancelada o ya usada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Ticket already used'),
            ])),
        ]
    )]
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
