<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Customer;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

test('ticket listing supports filtering by status', function () {
    // Create tickets with different statuses
    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => TicketStatus::OPEN,
    ]);

    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => TicketStatus::RESOLVED,
    ]);

    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => TicketStatus::IN_PROGRESS,
    ]);

    // Filter by OPEN status
    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?status=open');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [],
            'links',
            'meta',
        ]);

    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(1)
        ->and($data[0]['status'])->toBe(TicketStatus::OPEN->value);

    // Filter by RESOLVED status
    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?status=resolved');

    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['status'])->toBe(TicketStatus::RESOLVED->value);
});

test('ticket listing supports filtering by tag', function () {
    $tag1 = Tag::factory()->create(['name' => 'urgent']);
    $tag2 = Tag::factory()->create(['name' => 'bug']);

    $ticket1 = Ticket::factory()->create(['customer_id' => $this->customer->id]);
    $ticket2 = Ticket::factory()->create(['customer_id' => $this->customer->id]);
    $ticket3 = Ticket::factory()->create(['customer_id' => $this->customer->id]);

    // Attach tags
    $ticket1->tags()->attach($tag1->id);
    $ticket2->tags()->attach($tag1->id);
    $ticket3->tags()->attach($tag2->id);

    // Filter by tag ID
    $response = $this->actingAs($this->user)
        ->getJson("/api/tickets?tag={$tag1->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(2);

    // Filter by tag name
    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?tag=urgent');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(2);
});

test('ticket listing supports filtering by priority', function () {
    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'priority' => TicketPriority::HIGH,
    ]);

    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'priority' => TicketPriority::LOW,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?priority=high');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['priority'])->toBe(TicketPriority::HIGH->value);
});

test('ticket listing supports filtering by customer_id', function () {
    $customer2 = Customer::factory()->create();

    Ticket::factory()->create(['customer_id' => $this->customer->id]);
    Ticket::factory()->create(['customer_id' => $customer2->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/tickets?customer_id={$this->customer->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['customer']['id'])->toBe($this->customer->id);
});

test('ticket listing supports pagination', function () {
    Ticket::factory()->count(25)->create(['customer_id' => $this->customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [],
            'links',
            'meta' => [
                'current_page',
                'per_page',
                'total',
            ],
        ]);

    $meta = $response->json('meta');
    expect($meta['per_page'])->toBe(10)
        ->and($meta['total'])->toBe(25)
        ->and(count($response->json('data')))->toBe(10);
});

test('ticket listing supports search in subject and description', function () {
    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'subject' => 'Payment issue',
        'description' => 'Customer cannot pay',
    ]);

    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'subject' => 'Login problem',
        'description' => 'Cannot access account',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?search=payment');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(1)
        ->and($data[0]['subject'])->toContain('Payment');
});

test('ticket listing supports sorting', function () {
    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'priority' => TicketPriority::LOW,
        'created_at' => now()->subDay(),
    ]);

    Ticket::factory()->create([
        'customer_id' => $this->customer->id,
        'priority' => TicketPriority::URGENT,
        'created_at' => now(),
    ]);

    // Sort by priority descending
    $response = $this->actingAs($this->user)
        ->getJson('/api/tickets?sort_by=priority&sort_order=desc');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data[0]['priority'])->toBe(TicketPriority::URGENT->value)
        ->and($data[1]['priority'])->toBe(TicketPriority::LOW->value);
});

