<?php

namespace App\Http\Controllers\Support;

use App\Enums\TicketState;
use App\Http\Controllers\Concerns\PresentsTicketActivity;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * The IT support queue and the support team's view of a ticket.
 */
class SupportTicketController extends Controller
{
    use PresentsTicketActivity;

    /**
     * Tickets that are still being worked on.
     *
     * @var array<int, TicketState>
     */
    public const ACTIVE_STATES = [TicketState::Open, TicketState::Assigned, TicketState::InProgress, TicketState::WaitingForUser];

    /**
     * Created-date filter options, in days.
     *
     * @var array<string, array{label: string, days: int}>
     */
    protected const PERIODS = [
        '1' => ['label' => 'Last 24 hours', 'days' => 1],
        '7' => ['label' => 'Last 7 days', 'days' => 7],
        '30' => ['label' => 'Last 30 days', 'days' => 30],
        '90' => ['label' => 'Last 90 days', 'days' => 90],
    ];

    /**
     * Sortable columns and their default direction.
     *
     * @var array<string, string>
     */
    protected const SORTS = ['due' => 'asc', 'created' => 'desc', 'priority' => 'desc'];

    public function index(Request $request): View
    {
        return $this->queue($request, $request->query('tab'));
    }

    /**
     * The "My Assigned" page: the queue's "Assigned to me" tab.
     */
    public function assigned(Request $request): View
    {
        return $this->queue($request, 'mine');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('viewQueue', Ticket::class);

        $this->loadTicketActivity($ticket, $request->user()->can('viewInternalNotes', $ticket));

        return view('support.tickets.show', [
            'ticket' => $ticket,
            'timeline' => $this->timeline($ticket),
            'steps' => $this->workflowSteps($ticket),
            'requesterTicketCount' => Ticket::query()->reportedBy($ticket->creator)->count(),
            'categories' => TicketCategory::query()->ordered()->get(['id', 'name', 'is_active']),
            'priorities' => TicketPriority::query()->ordered()->get(['id', 'name']),
            'technicians' => $request->user()->can('assign', $ticket)
                ? User::query()
                    ->technicians()
                    ->withCount(['assignedTickets as active_tickets_count' => fn (Builder $query) => $query->inStates(...self::ACTIVE_STATES)])
                    ->orderBy('name')
                    ->get(['id', 'name', 'job_title'])
                : collect(),
        ]);
    }

    protected function queue(Request $request, ?string $requestedTab): View
    {
        Gate::authorize('viewQueue', Ticket::class);

        $user = $request->user();
        $tabs = $this->tabFilters($user);
        $activeTab = array_key_exists((string) $requestedTab, $tabs) ? $requestedTab : 'open';
        [$sort, $direction] = $this->sortFor($request, $activeTab);

        $tickets = Ticket::query()
            ->tap($tabs[$activeTab]['filter'])
            ->search($request->query('search'))
            ->when($request->integer('status'), fn (Builder $query, int $statusId) => $query->where('status_id', $statusId))
            ->when($request->integer('priority'), fn (Builder $query, int $priorityId) => $query->where('priority_id', $priorityId))
            ->when($request->integer('category'), fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->query('technician') === 'none', fn (Builder $query) => $query->whereNull('assigned_to'))
            ->when($request->integer('technician'), fn (Builder $query, int $technicianId) => $query->where('assigned_to', $technicianId))
            ->when(self::PERIODS[$request->query('period')] ?? null, fn (Builder $query, array $period) => $query->where('created_at', '>=', now()->subDays($period['days'])))
            ->tap(fn (Builder $query) => $this->applySort($query, $sort, $direction))
            ->orderByDesc('id')
            ->with(['status', 'priority', 'category', 'assignee', 'creator.department'])
            ->paginate(25)
            ->withQueryString();

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'tabs' => collect($tabs)->map(fn (array $tab, string $key): array => [
                'label' => $tab['label'],
                'href' => route('support.tickets.index', ['tab' => $key]),
                'count' => Ticket::query()->tap($tab['filter'])->count(),
                'active' => $key === $activeTab,
                'alert' => $key === 'overdue',
            ])->values()->all(),
            'activeTab' => $activeTab,
            'sort' => $sort,
            'direction' => $direction,
            'statuses' => TicketStatus::query()->ordered()->get(['id', 'name']),
            'priorities' => TicketPriority::query()->ordered()->get(['id', 'name']),
            'categories' => TicketCategory::query()->ordered()->get(['id', 'name']),
            'technicians' => User::query()->technicians()->orderBy('name')->get(['id', 'name']),
            'periods' => self::PERIODS,
            'hasFilters' => $request->hasAny(['search', 'status', 'priority', 'category', 'technician', 'period'])
                && collect($request->only(['search', 'status', 'priority', 'category', 'technician', 'period']))->filter()->isNotEmpty(),
        ]);
    }

    /**
     * The queue tabs, each with the query constraint it applies.
     *
     * @return array<string, array{label: string, filter: Closure(Builder<Ticket>): void}>
     */
    protected function tabFilters(User $user): array
    {
        $active = self::ACTIVE_STATES;

        return [
            'unassigned' => ['label' => 'Unassigned', 'filter' => fn (Builder $query) => $query->inStates(TicketState::Open)->whereNull('assigned_to')],
            'mine' => ['label' => 'Assigned to me', 'filter' => fn (Builder $query) => $query->inStates(...$active)->where('assigned_to', $user->id)],
            'open' => ['label' => 'All open', 'filter' => fn (Builder $query) => $query->inStates(...$active)],
            'overdue' => ['label' => 'Overdue', 'filter' => fn (Builder $query) => $query->overdue()],
            'resolved' => ['label' => 'Resolved', 'filter' => fn (Builder $query) => $query->inStates(TicketState::Resolved)],
            'closed' => ['label' => 'Closed', 'filter' => fn (Builder $query) => $query->inStates(TicketState::Closed)],
            'all' => ['label' => 'All tickets', 'filter' => fn (Builder $query) => $query],
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function sortFor(Request $request, string $tab): array
    {
        $defaultSort = in_array($tab, ['resolved', 'closed', 'all'], true) ? 'created' : 'due';
        $sort = array_key_exists((string) $request->query('sort'), self::SORTS) ? $request->query('sort') : $defaultSort;
        $direction = in_array($request->query('direction'), ['asc', 'desc'], true) ? $request->query('direction') : self::SORTS[$sort];

        return [$sort, $direction];
    }

    /**
     * @param  Builder<Ticket>  $query
     */
    protected function applySort(Builder $query, string $sort, string $direction): void
    {
        match ($sort) {
            // Tickets without a deadline go last whichever way the list is sorted.
            'due' => $query->orderByRaw('due_at is null')->orderBy('due_at', $direction),
            'priority' => $query->orderBy(
                TicketPriority::query()->select('level')->whereColumn('ticket_priorities.id', 'tickets.priority_id'),
                $direction,
            )->orderByRaw('due_at is null')->orderBy('due_at'),
            default => $query->orderBy('created_at', $direction),
        };
    }
}
