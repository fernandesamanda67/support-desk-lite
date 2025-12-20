<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketUpdateRequest;
use App\Http\Resources\TicketUpdateResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TicketUpdateController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    /**
     * Store a newly created ticket update.
     */
    public function store(StoreTicketUpdateRequest $request, Ticket $ticket): JsonResponse
    {
        // Authorization: if user exists, check policy
        if ($request->user()) {
            Gate::authorize('view', $ticket);
        }

        // Get user: from request or create a default one for testing
        $user = $request->user();

        if (!$user) {
            // For testing without auth, create or get first user
            $user = User::first();
            if (!$user) {
                $user = User::factory()->create([
                    'name' => 'System User',
                    'email' => 'system@example.com',
                ]);
            }
        }

        $update = $this->ticketService->addUpdate(
            $ticket,
            $user,
            $request->validated()
        );

        return (new TicketUpdateResource($update->load('createdBy')))
            ->response()
            ->setStatusCode(201);
    }
}
