<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class EventController extends Controller
{
    #[OA\Get(
        path: '/admin/events',
        summary: 'Listar eventos (admin)',
        description: 'Administradores ven todos los eventos. Organizadores ven solo los suyos.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Número de página', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de eventos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EventResource')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('create', Event::class);

        $user = auth()->user();
        $query = Event::with('organizer', 'ticketTypes');

        if (! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        return EventResource::collection($query->latest()->paginate(15));
    }

    #[OA\Post(
        path: '/admin/events',
        summary: 'Crear evento',
        description: 'Crea un nuevo evento con exactamente 3 tipos de entrada (General, VIP, Premium).',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'location', 'date', 'ticket_types'],
                properties: [
                    new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Concierto Rock'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Gran concierto de rock en vivo'),
                    new OA\Property(property: 'location', type: 'string', maxLength: 255, example: 'Estadio Nacional'),
                    new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-06-15T20:00:00'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(
                        property: 'ticket_types',
                        type: 'array',
                        minItems: 3,
                        maxItems: 3,
                        items: new OA\Items(
                            required: ['name', 'price', 'quantity'],
                            properties: [
                                new OA\Property(property: 'name', type: 'string', enum: ['General', 'VIP', 'Premium']),
                                new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0, example: 50.00),
                                new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 100),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Evento creado exitosamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/EventResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(StoreEventRequest $request): JsonResponse
    {
        $this->authorize('create', Event::class);

        $event = Event::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'location' => $request->location,
            'date' => $request->date,
            'is_active' => $request->is_active ?? true,
        ]);

        foreach ($request->ticket_types as $type) {
            $event->ticketTypes()->create($type);
        }

        $event->load('organizer', 'ticketTypes');

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/admin/events/{event}',
        summary: 'Ver evento (admin)',
        description: 'Devuelve el detalle de un evento. Requiere ser admin o propietario.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'event', in: 'path', required: true, description: 'ID del evento', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del evento',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/EventResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
        ]
    )]
    public function show(Event $event): EventResource
    {
        $this->authorize('update', $event);

        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }

    #[OA\Put(
        path: '/admin/events/{event}',
        summary: 'Actualizar evento',
        description: 'Actualiza los datos de un evento existente. Todos los campos son opcionales.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'event', in: 'path', required: true, description: 'ID del evento', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Concierto Rock Actualizado'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Descripción actualizada'),
                new OA\Property(property: 'location', type: 'string', maxLength: 255, example: 'Nuevo Estadio'),
                new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-07-20T21:00:00'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ])
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Evento actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/EventResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    #[OA\Patch(
        path: '/admin/events/{event}',
        summary: 'Actualizar parcialmente evento',
        description: 'Actualiza parcialmente los datos de un evento existente. Todos los campos son opcionales.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'event', in: 'path', required: true, description: 'ID del evento', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Concierto Rock Actualizado'),
                new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Descripción actualizada'),
                new OA\Property(property: 'location', type: 'string', maxLength: 255, example: 'Nuevo Estadio'),
                new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-07-20T21:00:00'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
            ])
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Evento actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/EventResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(UpdateEventRequest $request, Event $event): EventResource
    {
        $this->authorize('update', $event);

        $event->update($request->validated());
        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }

    #[OA\Delete(
        path: '/admin/events/{event}',
        summary: 'Eliminar evento',
        description: 'Elimina un evento. Requiere ser admin o propietario.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'event', in: 'path', required: true, description: 'ID del evento', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Evento eliminado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Event deleted successfully'),
            ])),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
        ]
    )]
    public function destroy(Event $event): JsonResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    #[OA\Get(
        path: '/admin/events/{event}/attendees',
        summary: 'Listar asistentes del evento',
        description: 'Devuelve los tickets vendidos para un evento con información del comprador y asistencia.',
        tags: ['Admin - Events'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'event', in: 'path', required: true, description: 'ID del evento', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Número de página', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de asistentes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketResource')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
        ]
    )]
    public function attendees(Event $event): AnonymousResourceCollection
    {
        $this->authorize('viewAttendees', $event);

        $tickets = $event->tickets()
            ->with('user', 'ticketType', 'attendance')
            ->paginate(30);

        return TicketResource::collection($tickets);
    }
}
