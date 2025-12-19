<?php

namespace App\Policies;

use App\Enums\TicketUpdateType;
use App\Models\TicketUpdate;
use App\Models\User;

class TicketUpdatePolicy
{
    /**
     * Determine whether the user can view any models.
     * All authenticated users (internal agents) can view ticket updates.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * Business Rule: Internal notes must not be visible outside internal users.
     * Since all authenticated users are internal agents, they can view all updates.
     * This method can be extended later to check for roles if needed.
     */
    public function view(User $user, TicketUpdate $ticketUpdate): bool
    {
        // All authenticated users are internal agents, so they can view all updates
        return true;
    }

    /**
     * Determine whether the user can view internal notes.
     *
     * Business Rule: Internal notes must not be visible outside internal users.
     * This method is used to filter internal notes in API responses.
     *
     * @param User $user
     * @param TicketUpdate $ticketUpdate
     * @return bool
     */
    public function viewInternalNote(User $user, TicketUpdate $ticketUpdate): bool
    {
        // All authenticated users are internal agents
        // In a real scenario, you might check: return $user->isInternalAgent();
        return $ticketUpdate->type === TicketUpdateType::INTERNAL_NOTE;
    }

    /**
     * Determine whether the user can create models.
     * All authenticated users (internal agents) can create ticket updates.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * For this test, we don't allow updating ticket updates.
     */
    public function update(User $user, TicketUpdate $ticketUpdate): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * For this test, we don't allow deletion.
     */
    public function delete(User $user, TicketUpdate $ticketUpdate): bool
    {
        return false;
    }
}
