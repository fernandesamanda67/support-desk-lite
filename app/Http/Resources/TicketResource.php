<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'assigned_user_id' => $this->assigned_user_id,
            'opened_at' => $this->opened_at,
            'resolved_at' => $this->resolved_at,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'updates' => TicketUpdateResource::collection(
                $this->whenLoaded('updates')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
