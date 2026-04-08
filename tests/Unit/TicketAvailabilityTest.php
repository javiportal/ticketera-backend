<?php

use App\Models\Ticket;
use App\Models\TicketType;

// ─── remainingTickets() ───────────────────────────────────────────────────────

it('calculates remaining tickets correctly', function () {
    $type = TicketType::factory()->create(['quantity' => 50]);
    Ticket::factory()->count(30)->create([
        'ticket_type_id' => $type->id,
        'status'         => 'valid',
    ]);

    expect($type->remainingTickets())->toBe(20);
});

it('counts used tickets as sold', function () {
    $type = TicketType::factory()->create(['quantity' => 100]);
    Ticket::factory()->count(10)->create(['ticket_type_id' => $type->id, 'status' => 'valid']);
    Ticket::factory()->count(25)->create(['ticket_type_id' => $type->id, 'status' => 'used']);

    // 35 tickets sold (valid + used), 65 remaining
    expect($type->remainingTickets())->toBe(65);
});

it('does not count cancelled tickets as sold', function () {
    $type = TicketType::factory()->create(['quantity' => 50]);
    Ticket::factory()->count(10)->create(['ticket_type_id' => $type->id, 'status' => 'cancelled']);

    // Cancelled tickets free up capacity
    expect($type->remainingTickets())->toBe(50);
});

it('returns zero when sold out', function () {
    $type = TicketType::factory()->create(['quantity' => 5]);
    Ticket::factory()->count(5)->create(['ticket_type_id' => $type->id, 'status' => 'valid']);

    expect($type->remainingTickets())->toBe(0);
});

it('returns full capacity when no tickets sold', function () {
    $type = TicketType::factory()->create(['quantity' => 200]);

    expect($type->remainingTickets())->toBe(200);
});

// ─── Ticket status enum ───────────────────────────────────────────────────────

it('creates a ticket with valid status by default', function () {
    $ticket = Ticket::factory()->create();

    expect($ticket->status)->toBe('valid');
});
