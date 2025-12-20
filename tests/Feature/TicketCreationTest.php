<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->ticketService = app(TicketService::class);
});

test('ticket creation persists correct defaults and relationships', function () {
    $data = [
        'customer_id' => $this->customer->id,
        'subject' => 'Test Subject',
        'description' => 'Test Description',
        'status' => TicketStatus::OPEN->value,
        'priority' => TicketPriority::MEDIUM->value,
    ];

    $ticket = $this->ticketService->createTicket($data);

    // Assert ticket was created
    expect($ticket)->toBeInstanceOf(Ticket::class)
        ->and($ticket->id)->toBeInt();

    // Assert relationships are loaded
    expect($ticket->relationLoaded('customer'))->toBeTrue()
        ->and($ticket->relationLoaded('assignedUser'))->toBeTrue();

    // Assert customer relationship
    expect($ticket->customer)->not->toBeNull()
        ->and($ticket->customer->id)->toBe($this->customer->id);

    // Assert assigned user is null by default
    expect($ticket->assignedUser)->toBeNull();

    // Assert default values
    expect($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->priority)->toBe(TicketPriority::MEDIUM)
        ->and($ticket->opened_at)->not->toBeNull()
        ->and($ticket->resolved_at)->toBeNull();
});

test('ticket creation with assigned user persists relationship', function () {
    $assignedUser = User::factory()->create();

    $data = [
        'customer_id' => $this->customer->id,
        'subject' => 'Test Subject',
        'description' => 'Test Description',
        'status' => TicketStatus::OPEN->value,
        'priority' => TicketPriority::HIGH->value,
        'assigned_user_id' => $assignedUser->id,
    ];

    $ticket = $this->ticketService->createTicket($data);

    // Assert assigned user relationship
    expect($ticket->assignedUser)->not->toBeNull()
        ->and($ticket->assignedUser->id)->toBe($assignedUser->id);
});

test('ticket creation via API endpoint persists correctly', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)
        ->postJson('/api/tickets', [
            'customer_id' => $customer->id,
            'subject' => 'API Test Subject',
            'description' => 'API Test Description',
            'status' => TicketStatus::OPEN->value,
            'priority' => TicketPriority::LOW->value,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'subject',
                'description',
                'status',
                'priority',
                'customer',
                'assigned_user',
            ],
        ]);

    // Assert ticket was persisted in database
    $this->assertDatabaseHas('tickets', [
        'customer_id' => $customer->id,
        'subject' => 'API Test Subject',
        'status' => TicketStatus::OPEN->value,
        'priority' => TicketPriority::LOW->value,
    ]);
});

