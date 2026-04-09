<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Ticketera API',
    description: 'API para gestión de eventos, venta de entradas y control de asistencia.',
    contact: new OA\Contact(email: 'admin@ticketera.com')
)]
#[OA\Server(url: '/api', description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Token',
    description: 'Token de autenticación Sanctum. Usa el endpoint /login para obtener uno.'
)]
#[OA\Tag(name: 'Auth', description: 'Autenticación de usuarios')]
#[OA\Tag(name: 'Events', description: 'Eventos públicos')]
#[OA\Tag(name: 'Tickets', description: 'Compra y listado de entradas')]
#[OA\Tag(name: 'Attendance', description: 'Validación de entradas')]
#[OA\Tag(name: 'Admin - Events', description: 'Gestión de eventos (admin/organizador)')]
#[OA\Tag(name: 'Admin - Users', description: 'Gestión de usuarios (admin)')]
class Controller extends BaseController
{
    use AuthorizesRequests;
}
