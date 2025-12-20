<?php

use App\Models\Customer;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'customer_id' => $this->customer->id,
    ]);
    $this->tag = Tag::factory()->create(['name' => 'urgent']);
});

test('can attach tag to ticket via PUT endpoint', function () {
    $response = $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Tag attached successfully',
        ])
        ->assertJsonStructure([
            'message',
            'ticket' => [
                'id',
                'tags' => [
                    '*' => ['id', 'name'],
                ],
            ],
        ]);

    // Verify tag is attached in database
    $this->ticket->refresh();
    expect($this->ticket->tags)->toHaveCount(1)
        ->and($this->ticket->tags->first()->id)->toBe($this->tag->id);
});

test('cannot attach same tag twice', function () {
    // Attach tag first time
    $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}")
        ->assertStatus(200);

    // Try to attach same tag again
    $response = $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Tag is already attached to this ticket.',
        ]);
});

test('can detach tag from ticket via DELETE endpoint', function () {
    // First attach the tag
    $this->ticket->tags()->attach($this->tag->id);

    // Then detach it
    $response = $this->actingAs($this->user)
        ->deleteJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Tag detached successfully',
        ])
        ->assertJsonStructure([
            'message',
            'ticket',
        ]);

    // Verify tag is detached in database
    $this->ticket->refresh();
    expect($this->ticket->tags)->toHaveCount(0);

    // Verify ticket is returned in response
    $json = $response->json();
    expect($json)->toHaveKey('ticket');
});

test('cannot detach tag that is not attached', function () {
    $response = $this->actingAs($this->user)
        ->deleteJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Tag is not attached to this ticket.',
        ]);
});

test('returns 404 when ticket does not exist', function () {
    $response = $this->actingAs($this->user)
        ->putJson("/api/tickets/99999/tags/{$this->tag->id}");

    $response->assertStatus(404);
    // Laravel returns ModelNotFoundException message, not our custom one
    expect($response->json('message'))->toContain('No query results');
});

test('returns 404 when tag does not exist', function () {
    $response = $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/99999");

    $response->assertStatus(404);
    // Laravel returns ModelNotFoundException message, not our custom one
    expect($response->json('message'))->toContain('No query results');
});

test('can attach multiple tags to ticket', function () {
    $tag2 = Tag::factory()->create(['name' => 'bug']);
    $tag3 = Tag::factory()->create(['name' => 'feature']);

    // Attach first tag
    $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}")
        ->assertStatus(200);

    // Attach second tag
    $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$tag2->id}")
        ->assertStatus(200);

    // Attach third tag
    $this->actingAs($this->user)
        ->putJson("/api/tickets/{$this->ticket->id}/tags/{$tag3->id}")
        ->assertStatus(200);

    // Verify all tags are attached
    $this->ticket->refresh();
    expect($this->ticket->tags)->toHaveCount(3)
        ->and($this->ticket->tags->pluck('id')->toArray())
        ->toContain($this->tag->id, $tag2->id, $tag3->id);
});

test('can detach one tag while keeping others', function () {
    $tag2 = Tag::factory()->create(['name' => 'bug']);
    $tag3 = Tag::factory()->create(['name' => 'feature']);

    // Attach all tags
    $this->ticket->tags()->attach([$this->tag->id, $tag2->id, $tag3->id]);

    // Detach one tag
    $this->actingAs($this->user)
        ->deleteJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}")
        ->assertStatus(200);

    // Verify only 2 tags remain
    $this->ticket->refresh();
    expect($this->ticket->tags)->toHaveCount(2)
        ->and($this->ticket->tags->pluck('id')->toArray())
        ->not->toContain($this->tag->id)
        ->toContain($tag2->id, $tag3->id);
});

test('tag operations work without authentication (for testing)', function () {
    // Test attach without authentication
    $response = $this->putJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");
    $response->assertStatus(200);

    // Test detach without authentication
    $response = $this->deleteJson("/api/tickets/{$this->ticket->id}/tags/{$this->tag->id}");
    $response->assertStatus(200);
});

