<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        summary: 'Registrar nuevo usuario',
        description: 'Crea un nuevo usuario con rol "client" y devuelve un token de autenticación.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Juan Pérez'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@email.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado exitosamente',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
                    new OA\Property(property: 'token', type: 'string', example: '1|abc123token...'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                ])
            ),
            new OA\Response(response: 422, description: 'Error de validación', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $clientRole = Role::where('name', 'client')->first();
        $user->roles()->attach($clientRole);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ], 201);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario y devuelve un token Bearer para usar en endpoints protegidos.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@email.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'token', type: 'string', example: '1|abc123token...'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                ])
            ),
            new OA\Response(response: 401, description: 'Credenciales inválidas', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials'),
            ])),
            new OA\Response(response: 403, description: 'Cuenta desactivada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Account deactivated'),
            ])),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account deactivated'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load('roles')),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token de acceso actual del usuario.',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
            ])),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
