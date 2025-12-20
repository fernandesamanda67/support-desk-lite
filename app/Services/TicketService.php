<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketUpdateType;
use App\Exceptions\InvalidTicketOperationException;
use App\Exceptions\TagNotFoundException;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketService
{

    private const MAX_PER_PAGE = 100;
    private const MAX_SEARCH_LENGTH = 255;
    /**
     * Create a new ticket.
     *
     * @param array<string, mixed> $data
     * @return Ticket
     */
    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::create([
                'customer_id' => $data['customer_id'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'assigned_user_id' => $data['assigned_user_id'] ?? null,
                'opened_at' => now(),
            ]);

            return $ticket->load(['customer', 'assignedUser']);
        });
    }

    /**
     * Update an existing ticket.
     *
     * Business Rule: When status becomes resolved, set resolved_at
     *
     * @param Ticket $ticket
     * @param array<string, mixed> $data
     * @return Ticket
     */
    public function updateTicket(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldStatus = $ticket->status;

            // Update allowed fields
            $ticket->fill([
                'subject' => $data['subject'] ?? $ticket->subject,
                'description' => $data['description'] ?? $ticket->description,
                'priority' => $data['priority'] ?? $ticket->priority,
                'assigned_user_id' => $data['assigned_user_id'] ?? $ticket->assigned_user_id,
                'status' => $data['status'] ?? $ticket->status,
            ]);

            // Business Rule 1: When status becomes resolved, set resolved_at
            if ($ticket->status === TicketStatus::RESOLVED && $oldStatus !== TicketStatus::RESOLVED) {
                $ticket->resolved_at = now();
            } elseif ($ticket->status !== TicketStatus::RESOLVED) {
                // Clear resolved_at if status changes away from resolved
                $ticket->resolved_at = null;
            }

            $ticket->save();

            return $ticket->load(['customer', 'assignedUser', 'tags']);
        });
    }

    /**
     * Add an update to a ticket.
     *
     * Business Rule 2: If ticket is resolved/closed and receives a comment,
     * it must reopen to open
     *
     * @param Ticket $ticket
     * @param User $user
     * @param array<string, mixed> $data
     * @return TicketUpdate
     */
    public function addUpdate(Ticket $ticket, User $user, array $data): TicketUpdate
    {
        return DB::transaction(function () use ($ticket, $user, $data) {
            $update = TicketUpdate::create([
                'ticket_id' => $ticket->id,
                'created_by_user_id' => $user->id,
                'body' => $data['body'],
                'type' => $data['type'],
            ]);

            // Business Rule 2: If ticket is resolved/closed and receives a comment,
            // it must reopen to open
            $isComment = $update->type === TicketUpdateType::COMMENT;
            $isResolvedOrClosed = in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED], true);

            if ($isComment && $isResolvedOrClosed) {
                $ticket->status = TicketStatus::OPEN;
                $ticket->resolved_at = null;
                $ticket->save();
            }

            return $update->load(['createdBy', 'ticket']);
        });
    }

    /**
     * Resolve tag from Tag instance or ID.
     *
     * @param Tag|int $tag
     * @return Tag
     * @throws TagNotFoundException
     */
    private function resolveTag(Tag|int $tag): Tag
    {
        if ($tag instanceof Tag) {
            return $tag;
        }

        $tagModel = Tag::find($tag);
        if (!$tagModel) {
            throw new TagNotFoundException("Tag with ID {$tag} not found.");
        }

        return $tagModel;
    }

    /**
     * Attach a tag to a ticket.
     *
     * @param Ticket $ticket
     * @param Tag|int $tag
     * @return void
     * @throws TagNotFoundException
     * @throws InvalidTicketOperationException
     */
    public function attachTag(Ticket $ticket, Tag|int $tag): void
    {
        $tagModel = $this->resolveTag($tag);
        $tagId = $tagModel->id;

        // Check if tag is already attached
        if ($ticket->tags()->where('tags.id', $tagId)->exists()) {
            throw new InvalidTicketOperationException("Tag is already attached to this ticket.");
        }

        $ticket->tags()->attach($tagId);
    }

    /**
     * Detach a tag from a ticket.
     *
     * @param Ticket $ticket
     * @param Tag|int $tag
     * @return void
     * @throws TagNotFoundException
     * @throws InvalidTicketOperationException
     */
    public function detachTag(Ticket $ticket, Tag|int $tag): void
    {
        $tagModel = $this->resolveTag($tag);
        $tagId = $tagModel->id;

        // Check if tag is attached
        if (!$ticket->tags()->where('tags.id', $tagId)->exists()) {
            throw new InvalidTicketOperationException("Tag is not attached to this ticket.");
        }

        $ticket->tags()->detach($tagId);
    }

    /**
     * Get tickets with filters, search, and sorting.
     *
     * Security and best practices:
     * - Validates enum values (defense in depth)
     * - Validates and limits perPage to prevent DoS
     * - Validates sort_order to prevent SQL injection
     * - Sanitizes search input (length limit)
     * - Type-safe ID filtering
     * - Correct tag filtering logic (numeric vs string)
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listTickets(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Validate and limit perPage to prevent DoS attacks
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        $query = Ticket::query()
            ->with(['customer', 'assignedUser', 'tags']);

        // Apply filters with validation
        if (isset($filters['status'])) {
            // Validate enum value (defense in depth)
            $statusValue = is_string($filters['status']) ? $filters['status'] : null;
            if ($statusValue && TicketStatus::tryFrom($statusValue)) {
                $query->where('status', $statusValue);
            }
        }

        if (isset($filters['priority'])) {
            // Validate enum value (defense in depth)
            $priorityValue = is_string($filters['priority']) ? $filters['priority'] : null;
            if ($priorityValue && TicketPriority::tryFrom($priorityValue)) {
                $query->where('priority', $priorityValue);
            }
        }

        if (isset($filters['customer_id'])) {
            // Type-safe ID filtering
            $customerId = filter_var($filters['customer_id'], FILTER_VALIDATE_INT);
            if ($customerId !== false && $customerId > 0) {
                $query->where('customer_id', $customerId);
            }
        }

        if (isset($filters['assigned_user_id'])) {
            // Type-safe ID filtering
            $userId = filter_var($filters['assigned_user_id'], FILTER_VALIDATE_INT);
            if ($userId !== false && $userId > 0) {
                $query->where('assigned_user_id', $userId);
            }
        }

        // Filter by tag
        if (isset($filters['tag'])) {
            $tagFilter = $filters['tag'];
            $query->whereHas('tags', function ($q) use ($tagFilter) {
                // If numeric, treat as ID; otherwise, treat as name
                if (is_numeric($tagFilter)) {
                    $q->where('tags.id', (int) $tagFilter);
                } else {
                    $q->where('tags.name', $tagFilter);
                }
            });
        }

        // Apply search with sanitization
        if (isset($filters['search']) && is_string($filters['search'])) {
            $search = trim($filters['search']);
            // Limit search length to prevent DoS
            if (strlen($search) > 0 && strlen($search) <= self::MAX_SEARCH_LENGTH) {
                // Laravel escapes LIKE automatically, but we ensure it's a string
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }

        // Apply sorting with validation
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSortFields = ['created_at', 'priority', 'status'];
        $allowedSortOrders = ['asc', 'desc'];

        // Validate sort_order to prevent SQL injection
        if (!in_array(strtolower($sortOrder), $allowedSortOrders, true)) {
            $sortOrder = 'desc';
        }

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }
}

