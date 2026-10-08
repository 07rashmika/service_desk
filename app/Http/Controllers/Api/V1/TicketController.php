<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketState;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\SupportTicketController;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Ticket lists for IT staff, picked with ?scope=. Employees always get their own tickets.
     */
    public const SCOPES = ['open', 'mine', 'unassigned', 'overdue', 'reported', 'all'];

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $filters = $request->validate([
            'scope' => ['nullable', Rule::in(self::SCOPES)],
            'status' => ['nullable', Rule::enum(TicketState::class)],
            'priority' => ['nullable', 'integer'],
            'category' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tickets = Ticket::query()
            ->tap(fn (Builder $query) => $this->applyScope($query, $request->user(), $filters['scope'] ?? 'open'))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->inStates(TicketState::from($status)))
            ->when($filters['priority'] ?? null, fn (Builder $query, int $priorityId) => $query->where('priority_id', $priorityId))
            ->when($filters['category'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->search($filters['search'] ?? null)
            ->with(['status', 'priority', 'category', 'creator', 'loggedBy', 'assignee'])
            ->latest()
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): JsonResponse
    {
        $ticket = $createTicket->handle(
            $request->requester(),
            $request->safe()->only(['title', 'description', 'category_id', 'priority_id']),
            $request->file('attachments', []),
            loggedBy: $request->user(),
        );

        return (new TicketResource($this->loadForDisplay($request, $ticket)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return new TicketResource($this->loadForDisplay($request, $ticket));
    }

    /**
     * Everything the ticket detail shows. Internal notes only for IT staff.
     */
    public static function loadForDisplay(Request $request, Ticket $ticket): Ticket
    {
        $includeInternal = $request->user()->can('viewInternalNotes', $ticket);

        return $ticket->load([
            'status',
            'priority',
            'category',
            'creator.department',
            'loggedBy',
            'assignee',
            'attachments' => fn ($query) => $query->whereNull('ticket_comment_id'),
            'comments' => fn ($query) => $query
                ->when(! $includeInternal, fn ($query) => $query->visibleToRequester())
                ->with(['user', 'attachments'])
                ->oldest()
                ->orderBy('id'),
        ]);
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applyScope(Builder $query, User $user, string $scope): void
    {
        if ($user->cannot('viewQueue', Ticket::class) || $scope === 'reported') {
            $query->reportedBy($user);

            return;
        }

        match ($scope) {
            'mine' => $query->inStates(...SupportTicketController::ACTIVE_STATES)->where('assigned_to', $user->id),
            'unassigned' => $query->inStates(TicketState::Open)->whereNull('assigned_to'),
            'overdue' => $query->overdue(),
            'all' => null,
            default => $query->inStates(...SupportTicketController::ACTIVE_STATES),
        };
    }
}
