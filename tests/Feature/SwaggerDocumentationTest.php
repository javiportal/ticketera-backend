<?php

use App\Models\User;

beforeEach(function () {
    $this->seed();
});

it('serves the swagger ui from the swagger route', function () {
    $this->get('/swagger')
        ->assertSuccessful()
        ->assertSee('SwaggerUIBundle', false);
});

it('documents patch update operations and login validation responses', function () {
    $response = $this->getJson('/api/docs/json')->assertSuccessful();
    $spec = $response->json();

    expect(data_get($spec, 'paths./admin/events/{event}.patch'))->not->toBeNull()
        ->and(data_get($spec, 'paths./admin/users/{user}.patch'))->not->toBeNull()
        ->and(data_get($spec, 'paths./login.post.responses.422'))->not->toBeNull()
        ->and(data_get($spec, 'paths./admin/users/{user}.put.requestBody.content.application/json.schema.properties.roles.items.enum'))
        ->toBe(['admin', 'organizer', 'client']);
});

it('validates admin user updates through the form request on patch', function () {
    $admin = User::where('email', 'admin@ticketera.com')->firstOrFail();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patchJson("/api/admin/users/{$user->id}", [
            'email' => 'correo-invalido',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
