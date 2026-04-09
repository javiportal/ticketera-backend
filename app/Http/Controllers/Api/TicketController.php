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
use OpenApi\Attributes as OA;

class TicketController extends Controller
{
    #[OA\Get(
        path: '/tickets',
        summary: 'Listar mis entradas',
        description: 'Devuelve las entradas del usuario autenticado con paginación.',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Número de página', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de entradas del usuario',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketResource')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $tickets = auth()->user()
            ->tickets()
            ->with('ticketType.event', 'attendance')
            ->latest()
            ->paginate(15);

        return TicketResource::collection($tickets);
    }

    #[OA\Post(
        path: '/tickets',
        summary: 'Comprar entrada',
        description: 'Compra una entrada para un evento. Solo usuarios con rol "client". No se puede comprar dos veces el mismo tipo de entrada.',
        tags: ['Tickets'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['event_id', 'ticket_type_id'],
                properties: [
                    new OA\Property(property: 'event_id', type: 'integer', example: 1, description: 'ID del evento'),
                    new OA\Property(property: 'ticket_type_id', type: 'integer', example: 1, description: 'ID del tipo de entrada'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entrada comprada exitosamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/TicketResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado (solo clientes)'),
            new OA\Response(response: 422, description: 'Error de validación o regla de negocio', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Sold out for this ticket type'),
            ])),
        ]
    )]
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
