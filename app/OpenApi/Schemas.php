<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@email.com'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'client')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'EventResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Concierto Rock'),
        new OA\Property(property: 'description', type: 'string', example: 'Gran concierto de rock en vivo'),
        new OA\Property(property: 'location', type: 'string', example: 'Estadio Nacional'),
        new OA\Property(property: 'date', type: 'string', format: 'date-time', example: '2026-06-15T20:00:00'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'organizer', ref: '#/components/schemas/UserResource'),
        new OA\Property(property: 'ticket_types', type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketTypeResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TicketTypeResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', enum: ['General', 'VIP', 'Premium'], example: 'General'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 50.00),
        new OA\Property(property: 'quantity', type: 'integer', example: 100),
        new OA\Property(property: 'remaining', type: 'integer', example: 85),
        new OA\Property(property: 'sold_out', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'TicketResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'status', type: 'string', enum: ['valid', 'used', 'cancelled'], example: 'valid'),
        new OA\Property(property: 'ticket_type', ref: '#/components/schemas/TicketTypeResource'),
        new OA\Property(property: 'event', ref: '#/components/schemas/EventResource'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
        new OA\Property(property: 'attendance', ref: '#/components/schemas/AttendanceResource', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AttendanceResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'ticket', ref: '#/components/schemas/TicketResource'),
        new OA\Property(property: 'checked_in_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 73),
    ]
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true),
        new OA\Property(property: 'last', type: 'string', nullable: true),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operación exitosa'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(
            type: 'array',
            items: new OA\Items(type: 'string')
        )),
    ]
)]
class Schemas
{
}
