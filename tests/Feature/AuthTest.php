<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

// ─── Helper ──────────────────────────────────────────────────────────────────

function ensureRoles(): void
{
    foreach (['admin', 'organizer', 'client'] as $name) {
        Role::firstOrCreate(['name' => $name]);
    }
}

// ─── Register ────────────────────────────────────────────────────────────────

it('registers a new user and returns a token', function () {
    ensureRoles();

    $this->postJson('/api/register', [
        'name'                  => 'Juan Pérez',
        'email'                 => 'juan@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(201)
      ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']]);

    $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
});

it('assigns the client role on register', function () {
    ensureRoles();

    $this->postJson('/api/register', [
        'name'                  => 'Ana García',
        'email'                 => 'ana@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'ana@example.com')->first()->hasRole('client'))->toBeTrue();
});

it('rejects registration with duplicate email', function () {
    ensureRoles();
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/register', [
        'name'                  => 'Otro',
        'email'                 => 'dup@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(422);
});

it('rejects registration with missing fields', function () {
    $this->postJson('/api/register', [])->assertStatus(422);
});

// ─── Login ───────────────────────────────────────────────────────────────────

it('logs in with correct credentials and returns a token', function () {
    $user = User::factory()->create(['password' => Hash::make('secret123'), 'is_active' => true]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secret123'])
         ->assertStatus(200)
         ->assertJsonStructure(['token', 'user']);
});

it('rejects login with wrong password', function () {
    $user = User::factory()->create(['password' => Hash::make('correct')]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
         ->assertStatus(401)
         ->assertJson(['message' => 'Invalid credentials']);
});

it('rejects login for non-existent user', function () {
    $this->postJson('/api/login', ['email' => 'noexiste@example.com', 'password' => 'password'])
         ->assertStatus(401);
});

it('rejects login for inactive user', function () {
    $user = User::factory()->create(['password' => Hash::make('password'), 'is_active' => false]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
         ->assertStatus(403)
         ->assertJson(['message' => 'Account deactivated']);
});

// ─── Logout ──────────────────────────────────────────────────────────────────

it('logs out and invalidates the token', function () {
    // Sanctum::actingAs crea un token real en DB (a diferencia de actingAs que usa TransientToken)
    $user = User::factory()->create(['is_active' => true]);
    Sanctum::actingAs($user);

    $this->postJson('/api/logout')
         ->assertStatus(200)
         ->assertJson(['message' => 'Logged out successfully']);
});

it('rejects logout when unauthenticated', function () {
    $this->postJson('/api/logout')->assertStatus(401);
});
