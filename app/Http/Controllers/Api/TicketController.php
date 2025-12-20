<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexTicketRequest;
use App\Http\Requests\ShowTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    private const DEFAULT_PER_PAGE = 15;

    /**
     * Display a listing of tickets with filters, search, and sorting.
     */
    public function index(IndexTicketRequest $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'priority',
            'tag',
            'customer_id',
            'assigned_user_id',
            'search',
            'sort_by',
            'sort_order',
        ]);

        $perPage = $request->get('per_page', self::DEFAULT_PER_PAGE);
        $tickets = $this->ticketService->listTickets($filters, $perPage);

        return TicketResource::collection($tickets)->response();
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createTicket($request->validated());

        $ticket->load(['customer', 'assignedUser', 'tags']);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(ShowTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->load(['customer', 'assignedUser', 'tags', 'updates.createdBy']);

        return (new TicketResource($ticket))->response();
    }

    /**
     * Update the specified ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket = $this->ticketService->updateTicket($ticket, $request->validated());

        return (new TicketResource($ticket))->response();
    }
}
