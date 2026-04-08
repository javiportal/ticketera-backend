<?php

use App\Models\Event;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;

beforeEach(function () {
    $this->seed();
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

function clientUser(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->roles()->attach(Role::where('name', 'client')->firstOrFail());
    return $user;
}

function organizerUser(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->roles()->attach(Role::where('name', 'organizer')->firstOrFail());
    return $user;
}

function activeEventWithType(int $quantity = 100): array
{
    $event = Event::factory()->create(['is_active' => true]);
    $type  = TicketType::factory()->create([
        'event_id' => $event->id,
        'quantity' => $quantity,
        'price'    => 25.00,
    ]);
    return [$event, $type];
}

// ─── Purchase success ─────────────────────────────────────────────────────────

it('allows a client to purchase a ticket', function () {
    $client = clientUser();
    [$event, $type] = activeEventWithType();

    $response = $this->actingAs($client)
        ->postJson('/api/tickets', [
            'event_id'       => $event->id,
            'ticket_type_id' => $type->id,
        ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['data' => ['id', 'code', 'status']]);

    $this->assertDatabaseHas('tickets', [
        'user_id'        => $client->id,
        'ticket_type_id' => $type->id,
        'status'         => 'valid',
    ]);
});

// ─── Auth guard ───────────────────────────────────────────────────────────────

it('rejects unauthenticated purchase', function () {
    [$event, $type] = activeEventWithType();

    $this->postJson('/api/tickets', [
        'event_id'       => $event->id,
        'ticket_type_id' => $type->id,
    ])->assertStatus(401);
});

// ─── Role guard ───────────────────────────────────────────────────────────────

it('rejects purchase by organizer role', function () {
    $organizer = organizerUser();
    [$event, $type] = activeEventWithType();

    $this->actingAs($organizer)
         ->postJson('/api/tickets', [
             'event_id'       => $event->id,
             'ticket_type_id' => $type->id,
         ])->assertStatus(403);
});

// ─── Business rules ───────────────────────────────────────────────────────────

it('rejects purchase when event is sold out', function () {
    $client = clientUser();
    [$event, $type] = activeEventWithType(quantity: 0);

    $this->actingAs($client)
         ->postJson('/api/tickets', [
             'event_id'       => $event->id,
             'ticket_type_id' => $type->id,
         ])->assertStatus(422)
           ->assertJson(['message' => 'Sold out for this ticket type']);
});

it('rejects duplicate purchase of same ticket type', function () {
    $client = clientUser();
    [$event, $type] = activeEventWithType();

    // First purchase
    $this->actingAs($client)
         ->postJson('/api/tickets', [
             'event_id'       => $event->id,
             'ticket_type_id' => $type->id,
         ])->assertStatus(201);

    // Second attempt
    $this->actingAs($client)
         ->postJson('/api/tickets', [
             'event_id'       => $event->id,
             'ticket_type_id' => $type->id,
         ])->assertStatus(422)
           ->assertJson(['message' => 'You already purchased this ticket type']);
});

it('rejects purchase for an inactive event', function () {
    $client = clientUser();
    $event  = Event::factory()->create(['is_active' => false]);
    $type   = TicketType::factory()->create(['event_id' => $event->id, 'quantity' => 100]);

    $this->actingAs($client)
         ->postJson('/api/tickets', [
             'event_id'       => $event->id,
             'ticket_type_id' => $type->id,
         ])->assertStatus(422)
           ->assertJson(['message' => 'Event is not active']);
});

it('rejects when ticket_type does not belong to the given event', function () {
    $client     = clientUser();
    $event1     = Event::factory()->create(['is_active' => true]);
    $event2     = Event::factory()->create(['is_active' => true]);
    $typeEvent2 = TicketType::factory()->create(['event_id' => $event2->id, 'quantity' => 50]);

    $this->actingAs($client)
         ->postJson('/api/tickets', [
             'event_id'       => $event1->id,   // wrong event
             'ticket_type_id' => $typeEvent2->id,
         ])->assertStatus(422);
});

// ─── My tickets ───────────────────────────────────────────────────────────────

it('lists only tickets belonging to the authenticated client', function () {
    $client = clientUser();
    $other  = clientUser();

    Ticket::factory()->count(3)->create(['user_id' => $client->id]);
    Ticket::factory()->count(2)->create(['user_id' => $other->id]);

    $this->actingAs($client)
         ->getJson('/api/tickets')
         ->assertStatus(200)
         ->assertJsonCount(3, 'data');
});

it('rejects unauthenticated access to ticket list', function () {
    $this->getJson('/api/tickets')->assertStatus(401);
});
