<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/admin/users',
        summary: 'Listar usuarios',
        description: 'Devuelve todos los usuarios con sus roles. Requiere permiso "manage-users".',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, description: 'Número de página', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de usuarios',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserResource')),
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
        Gate::authorize('manage-users');

        return UserResource::collection(User::with('roles')->paginate(15));
    }

    #[OA\Get(
        path: '/admin/users/{user}',
        summary: 'Ver usuario',
        description: 'Devuelve el detalle de un usuario con sus roles.',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'ID del usuario', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle del usuario',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function show(User $user): UserResource
    {
        Gate::authorize('manage-users');

        return new UserResource($user->load('roles'));
    }

    #[OA\Put(
        path: '/admin/users/{user}',
        summary: 'Actualizar usuario',
        description: 'Actualiza datos del usuario y opcionalmente sincroniza sus roles.',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'ID del usuario', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Juan Actualizado'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nuevo@email.com'),
                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                new OA\Property(
                    property: 'roles',
                    type: 'array',
                    items: new OA\Items(type: 'string', enum: ['admin', 'organizer', 'client', 'checker']),
                    example: ['client', 'organizer']
                ),
            ])
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario actualizado',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function update(Request $request, User $user): UserResource
    {
        Gate::authorize('manage-users');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->update(collect($validated)->except('roles')->toArray());

        if (isset($validated['roles'])) {
            $roleIds = Role::whereIn('name', $validated['roles'])->pluck('id');
            $user->roles()->sync($roleIds);
        }

        return new UserResource($user->load('roles'));
    }

    #[OA\Delete(
        path: '/admin/users/{user}',
        summary: 'Eliminar usuario',
        description: 'Elimina un usuario del sistema.',
        tags: ['Admin - Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, description: 'ID del usuario', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario eliminado', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'User deleted successfully'),
            ])),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Usuario no encontrado'),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('manage-users');

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
