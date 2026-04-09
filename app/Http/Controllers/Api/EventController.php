<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class EventController extends Controller
{
    #[OA\Get(
        path: '/events',
        summary: 'Listar eventos activos',
        description: 'Devuelve una lista paginada de eventos activos con su organizador y tipos de entrada.',
        tags: ['Events'],
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
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $events = Event::where('is_active', true)
            ->with('organizer', 'ticketTypes')
            ->orderBy('date')
            ->paginate(15);

        return EventResource::collection($events);
    }

    #[OA\Get(
        path: '/events/{event}',
        summary: 'Ver detalle de evento',
        description: 'Devuelve la información completa de un evento activo.',
        tags: ['Events'],
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
            new OA\Response(response: 404, description: 'Evento no encontrado'),
            new OA\Response(response: 422, description: 'Evento no activo', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Event is not active'),
            ])),
        ]
    )]
    public function show(Event $event): EventResource
    {
        if (!$event->is_active) {
            abort(422, 'Event is not active');
        }

        $event->load('organizer', 'ticketTypes');

        return new EventResource($event);
    }
}
