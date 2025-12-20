<?php

use App\Enums\TicketUpdateType;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketUpdate;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'customer_id' => $this->customer->id,
    ]);
});

test('internal notes are visible to authenticated internal users', function () {
    // Business Rule: Internal notes must not be visible outside internal users
    // This test verifies that authenticated users (internal agents) CAN see internal notes

    // Create updates with different types
    TicketUpdate::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'type' => TicketUpdateType::COMMENT,
        'body' => 'Public comment',
    ]);

    TicketUpdate::factory()->internalNote()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'body' => 'Internal note',
    ]);

    // Authenticated user (internal agent) should see both
    $response = $this->actingAs($this->user)
        ->getJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'updates' => [],
            ],
        ]);

    $updates = $response->json('data.updates');

    // Internal users should see both comment and internal note
    expect($updates)->toBeArray()
        ->and(count($updates))->toBe(2, 'Internal users should see all updates');

    // Verify internal note is visible to authenticated internal users
    $internalNote = collect($updates)->firstWhere('type', TicketUpdateType::INTERNAL_NOTE->value);
    expect($internalNote)->not->toBeNull('Internal notes should be visible to authenticated internal users')
        ->and($internalNote['body'])->toBe('Internal note');
});

test('internal notes are not visible to unauthenticated users (public/customer-facing)', function () {
    // Business Rule: Internal notes must not be visible outside internal users
    // This test verifies that unauthenticated requests cannot see internal notes

    // Create a comment (should be visible) and an internal note (should be hidden)
    TicketUpdate::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'type' => TicketUpdateType::COMMENT,
        'body' => 'Public comment',
    ]);

    TicketUpdate::factory()->internalNote()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'body' => 'Internal note - should be hidden from public',
    ]);

    // Simulate a request without authentication (public/customer-facing)
    $response = $this->getJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(200);

    $updates = $response->json('data.updates') ?? [];

    // Verify that internal notes are filtered out for unauthenticated users
    // The TicketUpdateResource uses the Policy to filter internal notes
    $internalNotes = collect($updates)->filter(
        fn ($update) => isset($update['type']) && $update['type'] === TicketUpdateType::INTERNAL_NOTE->value
    );

    // Internal notes must not be visible outside internal users
    expect($internalNotes->count())->toBe(0, 'Internal notes should not be visible to unauthenticated users');

    // But comments should still be visible
    $comments = collect($updates)->filter(
        fn ($update) => isset($update['type']) && $update['type'] === TicketUpdateType::COMMENT->value
    );
    expect($comments->count())->toBeGreaterThan(0, 'Comments should be visible to everyone');
});

test('policy enforces that internal notes are only visible to internal users', function () {
    // Business Rule: Internal notes must not be visible outside internal users
    // This test verifies that the Policy is correctly enforcing the authorization rule

    $internalNote = TicketUpdate::factory()->internalNote()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'body' => 'Internal note',
    ]);

    // Authenticated user (internal agent) should be able to view via policy
    $response = $this->actingAs($this->user)
        ->getJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(200);

    $updates = $response->json('data.updates');
    $internalNoteResponse = collect($updates)->firstWhere('type', TicketUpdateType::INTERNAL_NOTE->value);

    // Policy check: authenticated users (internal agents) can view internal notes
    expect($internalNoteResponse)->not->toBeNull('Policy should allow internal users to view internal notes')
        ->and($internalNoteResponse['body'])->toBe('Internal note');

    // Verify the policy method is being used
    // The TicketUpdateResource uses $request->user()?->can('viewInternalNote', $this->resource)
    expect($this->user->can('viewInternalNote', $internalNote))->toBeTrue('Policy should return true for internal users');
});

test('ticket update resource filters internal notes based on policy', function () {
    $comment = TicketUpdate::factory()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'type' => TicketUpdateType::COMMENT,
        'body' => 'Public comment',
    ]);

    $internalNote = TicketUpdate::factory()->internalNote()->create([
        'ticket_id' => $this->ticket->id,
        'created_by_user_id' => $this->user->id,
        'body' => 'Internal note',
    ]);

    // When authenticated, should see both
    $response = $this->actingAs($this->user)
        ->getJson("/api/tickets/{$this->ticket->id}");

    $updates = $response->json('data.updates');
    expect(count($updates))->toBe(2);

    // Verify internal note is present for authenticated users
    $hasInternalNote = collect($updates)->contains(
        fn ($update) => $update['type'] === TicketUpdateType::INTERNAL_NOTE->value
    );
    expect($hasInternalNote)->toBeTrue();
});

