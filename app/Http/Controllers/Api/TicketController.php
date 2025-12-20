<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    /**
     * Display a listing of tickets with filters, search, and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->with(['customer', 'assignedUser', 'tags']);

        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);
        $this->applySorting($query, $request);

        $perPage = $request->get('per_page', 15);
        $tickets = $query->paginate($perPage);

        return TicketResource::collection($tickets)->response();
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, Request $request): void
    {
        $filters = [
            'status' => 'status',
            'priority' => 'priority',
            'customer_id' => 'customer_id',
            'assigned_user_id' => 'assigned_user_id',
        ];

        foreach ($filters as $key => $column) {
            if ($request->has($key)) {
                $query->where($column, $request->get($key));
            }
        }

        // Filter by tag
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag)
                    ->orWhere('tags.name', $request->tag);
            });
        }
    }

    /**
     * Apply search to the query.
     */
    private function applySearch($query, Request $request): void
    {
        if (!$request->has('search')) {
            return;
        }

        $search = $request->get('search');
        $query->where(function ($q) use ($search) {
            $q->where('subject', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['created_at', 'priority', 'status'];

        if (in_array($sortBy, $allowedSortFields, true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->createTicket($request->validated());

        // Load relationships (tags will be empty array initially)
        $ticket->load(['customer', 'assignedUser', 'tags']);

        return (new TicketResource($ticket))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        // Authorization: if user exists, check policy
        if ($request->user()) {
            Gate::authorize('view', $ticket);
        }

        $ticket->load(['customer', 'assignedUser', 'tags', 'updates.createdBy']);

        return (new TicketResource($ticket))->response();
    }

    /**
     * Update the specified ticket.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        // Authorization handled by UpdateTicketRequest
        $ticket = $this->ticketService->updateTicket($ticket, $request->validated());

        return (new TicketResource($ticket))->response();
    }
}
