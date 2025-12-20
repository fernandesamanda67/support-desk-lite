<?php

use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketUpdate;
use App\Models\User;
use App\Services\TicketService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->ticketService = app(TicketService::class);
});

test('resolving a ticket sets resolved_at', function () {
    $ticket = Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => TicketStatus::OPEN,
        'resolved_at' => null,
    ]);

    // Update ticket to resolved status
    $this->ticketService->updateTicket($ticket, [
        'status' => TicketStatus::RESOLVED->value,
    ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::RESOLVED)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('changing status away from resolved clears resolved_at', function () {
    $ticket = Ticket::factory()->resolved()->create([
        'customer_id' => $this->customer->id,
    ]);

    expect($ticket->resolved_at)->not->toBeNull();

    // Change status to closed
    $this->ticketService->updateTicket($ticket, [
        'status' => TicketStatus::CLOSED->value,
    ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::CLOSED)
        ->and($ticket->resolved_at)->toBeNull();
});

test('adding a comment to a resolved ticket reopens it to open', function () {
    $ticket = Ticket::factory()->resolved()->create([
        'customer_id' => $this->customer->id,
    ]);

    expect($ticket->status)->toBe(TicketStatus::RESOLVED);

    // Add a comment update
    $this->ticketService->addUpdate($ticket, $this->user, [
        'body' => 'Customer has a follow-up question',
        'type' => 'comment',
    ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->resolved_at)->toBeNull();
});

test('adding a comment to a closed ticket reopens it to open', function () {
    $ticket = Ticket::factory()->closed()->create([
        'customer_id' => $this->customer->id,
    ]);

    expect($ticket->status)->toBe(TicketStatus::CLOSED);

    // Add a comment update
    $this->ticketService->addUpdate($ticket, $this->user, [
        'body' => 'Customer needs more help',
        'type' => 'comment',
    ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->resolved_at)->toBeNull();
});

test('adding a non-comment update does not reopen resolved ticket', function () {
    $ticket = Ticket::factory()->resolved()->create([
        'customer_id' => $this->customer->id,
    ]);

    $originalResolvedAt = $ticket->resolved_at;

    // Add an internal note (not a comment)
    $this->ticketService->addUpdate($ticket, $this->user, [
        'body' => 'Internal note about the ticket',
        'type' => 'internal_note',
    ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::RESOLVED)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('status transition via API endpoint sets resolved_at correctly', function () {
    $ticket = Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => TicketStatus::OPEN,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/tickets/{$ticket->id}", [
            'status' => TicketStatus::RESOLVED->value,
        ]);

    $response->assertStatus(200);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::RESOLVED)
        ->and($ticket->resolved_at)->not->toBeNull();
});

test('adding comment via API endpoint reopens resolved ticket', function () {
    $ticket = Ticket::factory()->resolved()->create([
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/tickets/{$ticket->id}/updates", [
            'body' => 'Follow-up question',
            'type' => 'comment',
        ]);

    $response->assertStatus(201);

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->resolved_at)->toBeNull();
});

