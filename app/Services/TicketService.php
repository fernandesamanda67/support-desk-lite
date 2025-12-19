<?php

namespace App\Services;

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
}

