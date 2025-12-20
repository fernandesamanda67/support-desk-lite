<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachTagRequest;
use App\Http\Requests\DetachTagRequest;
use App\Http\Resources\TicketResource;
use App\Models\Tag;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    /**
     * Attach a tag to a ticket.
     */
    public function attach(AttachTagRequest $request, Ticket $ticket, Tag $tag): JsonResponse
    {
        $this->ticketService->attachTag($ticket, $tag);

        $ticket->load('tags');

        return response()->json([
            'message' => 'Tag attached successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }

    /**
     * Detach a tag from a ticket.
     */
    public function detach(DetachTagRequest $request, Ticket $ticket, Tag $tag): JsonResponse
    {
        $this->ticketService->detachTag($ticket, $tag);

        $ticket->load('tags');

        return response()->json([
            'message' => 'Tag detached successfully',
            'ticket' => new TicketResource($ticket),
        ]);
    }
}
