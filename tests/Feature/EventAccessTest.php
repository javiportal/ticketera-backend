<?php

use App\Models\Event;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;

// ─── Helper ───────────────────────────────────────────────────────────────────

function makeRoleUser(string $roleName): User
{
    $user = User::factory()->create(['is_active' => true]);

    $role = Role::firstOrCreate(['name' => $roleName]);

    // Asignar permisos según el rol (igual que el DatabaseSeeder)
    $permissionMap = [
        'admin'     => ['manage-users', 'manage-events', 'sell-tickets', 'validate-tickets', 'view-reports'],
        'organizer' => ['manage-events', 'validate-tickets', 'view-reports'],
        'client'    => ['sell-tickets'],
    ];

    foreach ($permissionMap[$roleName] ?? [] as $permName) {
        $perm = Permission::firstOrCreate(['name' => $permName]);
        $role->permissions()->syncWithoutDetaching($perm->id);
    }

    $user->roles()->attach($role);
    return $user;
}

// ─── Public endpoints ─────────────────────────────────────────────────────────

it('lists active events publicly', function () {
    Event::factory()->count(3)->create(['is_active' => true]);
    Event::factory()->count(2)->create(['is_active' => false]);

    $response = $this->getJson('/api/events');
    $response->assertStatus(200);

    collect($response->json('data'))->each(
        fn ($event) => expect($event['is_active'])->toBeTrue()
    );
});

it('shows event detail with ticket types publicly', function () {
    $event = Event::factory()->create(['is_active' => true]);
    TicketType::factory()->count(3)->create(['event_id' => $event->id]);

    $this->getJson("/api/events/{$event->id}")
         ->assertStatus(200)
         ->assertJsonStructure(['data' => ['id', 'title', 'ticket_types']]);
});

it('returns 404 for non-existent event', function () {
    $this->getJson('/api/events/9999')->assertStatus(404);
});

// ─── Admin: create event ──────────────────────────────────────────────────────

it('allows organizer to create an event', function () {
    $organizer = makeRoleUser('organizer');

    $this->actingAs($organizer)
         ->postJson('/api/admin/events', [
             'title'        => 'Festival de Jazz',
             'description'  => 'Un festival increíble',
             'location'     => 'Teatro Nacional',
             'date'         => now()->addMonth()->toDateTimeString(),
             'is_active'    => true,
             'ticket_types' => [
                 ['name' => 'General', 'price' => 20.00, 'quantity' => 200],
                 ['name' => 'VIP',     'price' => 60.00, 'quantity' => 50],
                 ['name' => 'Premium', 'price' => 100.00, 'quantity' => 20],
             ],
         ])->assertStatus(201)
           ->assertJsonPath('data.title', 'Festival de Jazz');

    $this->assertDatabaseHas('events', ['title' => 'Festival de Jazz']);
});

it('allows admin to create an event', function () {
    $admin = makeRoleUser('admin');

    $this->actingAs($admin)
         ->postJson('/api/admin/events', [
             'title'        => 'Expo Tech 2026',
             'description'  => 'Tecnología del futuro',
             'location'     => 'Centro de Convenciones',
             'date'         => now()->addWeeks(3)->toDateTimeString(),
             'is_active'    => true,
             'ticket_types' => [
                 ['name' => 'General', 'price' => 15.00, 'quantity' => 500],
                 ['name' => 'VIP',     'price' => 40.00, 'quantity' => 100],
                 ['name' => 'Premium', 'price' => 80.00, 'quantity' => 30],
             ],
         ])->assertStatus(201);
});

it('forbids client from creating an event', function () {
    $client = makeRoleUser('client');

    $this->actingAs($client)
         ->postJson('/api/admin/events', [
             'title'        => 'Intento fallido',
             'description'  => '...',
             'location'     => 'Ningún lado',
             'date'         => now()->addMonth()->toDateTimeString(),
             'ticket_types' => [
                 ['name' => 'General', 'price' => 10.00, 'quantity' => 100],
                 ['name' => 'VIP',     'price' => 30.00, 'quantity' => 50],
                 ['name' => 'Premium', 'price' => 60.00, 'quantity' => 20],
             ],
         ])->assertStatus(403);
});

it('rejects unauthenticated event creation', function () {
    $this->postJson('/api/admin/events', [])->assertStatus(401);
});

// ─── Admin: attendees report ──────────────────────────────────────────────────

it('allows organizer to view their event attendees', function () {
    $organizer = makeRoleUser('organizer');
    $event     = Event::factory()->create(['user_id' => $organizer->id]);
    $type      = TicketType::factory()->create(['event_id' => $event->id]);
    Ticket::factory()->count(5)->create(['ticket_type_id' => $type->id, 'status' => 'used']);

    $this->actingAs($organizer)
         ->getJson("/api/admin/events/{$event->id}/attendees")
         ->assertStatus(200);
});

it('forbids organizer from viewing another organizer attendees', function () {
    $organizer1 = makeRoleUser('organizer');
    $organizer2 = makeRoleUser('organizer');
    $event      = Event::factory()->create(['user_id' => $organizer2->id]);

    $this->actingAs($organizer1)
         ->getJson("/api/admin/events/{$event->id}/attendees")
         ->assertStatus(403);
});

it('forbids client from viewing attendees', function () {
    $client = makeRoleUser('client');
    $event  = Event::factory()->create();

    $this->actingAs($client)
         ->getJson("/api/admin/events/{$event->id}/attendees")
         ->assertStatus(403);
});

// ─── Ticket validation at door ────────────────────────────────────────────────

it('allows organizer to validate a valid ticket', function () {
    $organizer = makeRoleUser('organizer');
    $ticket    = Ticket::factory()->create(['status' => 'valid']);

    $this->actingAs($organizer)
         ->postJson("/api/tickets/{$ticket->code}/validate")
         ->assertStatus(201)
         ->assertJsonStructure(['data' => ['id', 'checked_in_at']]);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'used']);
    $this->assertDatabaseHas('attendances', ['ticket_id' => $ticket->id]);
});

it('rejects validation of an already used ticket', function () {
    $organizer = makeRoleUser('organizer');
    $ticket    = Ticket::factory()->create(['status' => 'used']);

    $this->actingAs($organizer)
         ->postJson("/api/tickets/{$ticket->code}/validate")
         ->assertStatus(422)
         ->assertJson(['message' => 'Ticket already used']);
});

it('rejects validation of a cancelled ticket', function () {
    $organizer = makeRoleUser('organizer');
    $ticket    = Ticket::factory()->create(['status' => 'cancelled']);

    $this->actingAs($organizer)
         ->postJson("/api/tickets/{$ticket->code}/validate")
         ->assertStatus(422)
         ->assertJson(['message' => 'Ticket has been cancelled']);
});

it('returns 404 for unknown ticket code', function () {
    $organizer = makeRoleUser('organizer');

    $this->actingAs($organizer)
         ->postJson('/api/tickets/FAKE-CODE-0000/validate')
         ->assertStatus(404);
});

it('forbids client from validating tickets', function () {
    $client = makeRoleUser('client');
    $ticket = Ticket::factory()->create(['status' => 'valid']);

    $this->actingAs($client)
         ->postJson("/api/tickets/{$ticket->code}/validate")
         ->assertStatus(403);
});

it('rejects unauthenticated ticket validation', function () {
    $ticket = Ticket::factory()->create(['status' => 'valid']);

    $this->postJson("/api/tickets/{$ticket->code}/validate")
         ->assertStatus(401);
});
