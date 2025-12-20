<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketUpdateRequest;
use App\Http\Resources\TicketUpdateResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\AuthenticationException;

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
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('User must be authenticated to create ticket updates.');
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
