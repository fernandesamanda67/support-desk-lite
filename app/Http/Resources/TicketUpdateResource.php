<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Business Rule: Internal notes must not be visible outside internal users.
     * This resource filters internal notes based on the user's permissions.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if this is an internal note and if user can view it
        $canViewInternalNote = $this->isInternalNote()
            ? ($request->user()?->can('viewInternalNote', $this->resource) ?? false)
            : true;

        // If user cannot view internal note, return null or empty
        if ($this->isInternalNote() && !$canViewInternalNote) {
            return [];
        }

        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'created_by_user_id' => $this->created_by_user_id,
            'body' => $this->body,
            'type' => $this->type->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
