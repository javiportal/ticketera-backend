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

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('manage-users');

        return UserResource::collection(User::with('roles')->paginate(15));
    }

    public function show(User $user): UserResource
    {
        Gate::authorize('manage-users');

        return new UserResource($user->load('roles'));
    }

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

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('manage-users');

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}