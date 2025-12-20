<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Tag;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    /**
     * Attach or detach tags to/from a ticket.
     *
     * Payload: { "tag_id": 1, "action": "attach" } or { "tag_id": 1, "action": "detach" }
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        // Authorization: if user exists, check policy
        if ($request->user()) {
            Gate::authorize('update', $ticket);
        }

        $validated = $request->validate([
            'tag_id' => ['required', 'integer', 'exists:tags,id'],
            'action' => ['required', 'string', Rule::in(['attach', 'detach'])],
        ]);

        $tag = Tag::findOrFail($validated['tag_id']);

        match ($validated['action']) {
            'attach' => $this->ticketService->attachTag($ticket, $tag),
            'detach' => $this->ticketService->detachTag($ticket, $tag),
        };

        // Reload ticket with tags
        $ticket->load('tags');

        return response()->json([
            'message' => "Tag {$validated['action']}ed successfully",
            'ticket' => new TicketResource($ticket),
        ]);
    }
}
