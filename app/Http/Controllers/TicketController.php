<?php

namespace App\Http\Controllers;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketState;
use App\Http\Controllers\Concerns\PresentsTicketActivity;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    use PresentsTicketActivity;

    /**
     * The tabs on "My Tickets" and the workflow states each one shows.
     *
     * @var array<string, array{label: string, states: array<int, TicketState>}>
     */
    protected const TABS = [
        'all' => ['label' => 'All', 'states' => []],
        'open' => ['label' => 'Open', 'states' => [TicketState::Open, TicketState::Assigned]],
        'in_progress' => ['label' => 'In Progress', 'states' => [TicketState::InProgress]],
        'waiting' => ['label' => 'Waiting for me', 'states' => [TicketState::WaitingForUser]],
        'resolved' => ['label' => 'Resolved', 'states' => [TicketState::Resolved]],
        'closed' => ['label' => 'Closed', 'states' => [TicketState::Closed]],
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $user = $request->user();
        $activeTab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $countsByState = $this->countsByState(Ticket::query()->reportedBy($user));

        $tickets = Ticket::query()
            ->reportedBy($user)
            ->when(self::TABS[$activeTab]['states'], fn ($query, array $states) => $query->inStates(...$states))
            ->search($request->query('search'))
            ->when($request->integer('category'), fn ($query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($request->integer('priority'), fn ($query, int $priorityId) => $query->where('priority_id', $priorityId))
            ->with(['status', 'priority', 'category', 'assignee'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $resolvedDurations = Ticket::query()
            ->reportedBy($user)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at'])
            ->map(fn (Ticket $ticket): float => $ticket->created_at->diffInMinutes($ticket->resolved_at));

        return view('tickets.index', [
            'tickets' => $tickets,
            'tabs' => $this->tabs($activeTab, $countsByState, $request),
            'stats' => [
                'open' => $this->countFor($countsByState, TicketState::Open, TicketState::Assigned),
                'waiting' => $this->countFor($countsByState, TicketState::WaitingForUser),
                'inProgress' => $this->countFor($countsByState, TicketState::InProgress),
                'averageResolutionHours' => $resolvedDurations->isEmpty() ? null : round($resolvedDurations->avg() / 60, 1),
            ],
            'categories' => TicketCategory::query()->ordered()->get(['id', 'name']),
            'priorities' => TicketPriority::query()->ordered()->get(['id', 'name']),
            'hasFilters' => $request->filled('search') || $request->filled('category') || $request->filled('priority'),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Ticket::class);

        $priorities = TicketPriority::query()->ordered()->get();

        return view('tickets.create', [
            'requesters' => $request->user()->can('createOnBehalf', Ticket::class)
                ? User::query()->where('is_active', true)->whereKeyNot($request->user()->id)->with('department')->orderBy('name')->get(['id', 'name', 'email', 'department_id'])
                : null,
            'categories' => TicketCategory::query()->active()->ordered()->get(),
            'priorities' => $priorities,
            'defaultPriorityId' => $priorities->firstWhere('slug', 'medium')?->id ?? $priorities->first()?->id,
        ]);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): RedirectResponse
    {
        $requester = $request->requester();

        $ticket = $createTicket->handle(
            $requester,
            $request->safe()->only(['title', 'description', 'category_id', 'priority_id']),
            $request->file('attachments', []),
            loggedBy: $request->user(),
        );

        if ($ticket->logged_by !== null) {
            return redirect()
                ->route('support.tickets.show', $ticket)
                ->with('success', "Ticket {$ticket->reference} was logged for {$requester->name}. They can follow it under My Tickets.");
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->reference} was created. We'll keep you posted here.");
    }

    /**
     * The requester's view of a ticket. IT staff opening someone else's ticket get the support view.
     */
    public function show(Request $request, Ticket $ticket): View|RedirectResponse
    {
        Gate::authorize('view', $ticket);

        if ($request->user()->can('viewQueue', Ticket::class) && $ticket->created_by !== $request->user()->id) {
            return redirect()->route('support.tickets.show', $ticket);
        }

        $this->loadTicketActivity($ticket, $request->user()->can('viewInternalNotes', $ticket));

        return view('tickets.show', [
            'ticket' => $ticket,
            'timeline' => $this->timeline($ticket),
            'steps' => $this->workflowSteps($ticket),
        ]);
    }

    /**
     * Number of tickets in each workflow state, keyed by state value.
     *
     * @param  Builder<Ticket>  $query
     * @return Collection<string, int>
     */
    protected function countsByState($query): Collection
    {
        $slugsById = TicketStatus::query()->pluck('slug', 'id');

        return $query
            ->toBase()
            ->selectRaw('status_id, count(*) as aggregate')
            ->groupBy('status_id')
            ->pluck('aggregate', 'status_id')
            ->mapWithKeys(fn ($count, $statusId): array => [$slugsById[$statusId]->value => (int) $count]);
    }

    /**
     * @param  Collection<string, int>  $countsByState
     */
    protected function countFor(Collection $countsByState, TicketState ...$states): int
    {
        return collect($states)->sum(fn (TicketState $state): int => $countsByState->get($state->value, 0));
    }

    /**
     * @param  Collection<string, int>  $countsByState
     * @return array<int, array{label: string, href: string, count: int, active: bool, alert: bool}>
     */
    protected function tabs(string $activeTab, Collection $countsByState, Request $request): array
    {
        return collect(self::TABS)
            ->map(fn (array $tab, string $key): array => [
                'label' => $tab['label'],
                'href' => route('tickets.index', [...$request->except('tab', 'page'), 'tab' => $key === 'all' ? null : $key]),
                'count' => $tab['states'] === [] ? $countsByState->sum() : $this->countFor($countsByState, ...$tab['states']),
                'active' => $key === $activeTab,
                'alert' => $key === 'waiting' && $this->countFor($countsByState, TicketState::WaitingForUser) > 0,
            ])
            ->values()
            ->all();
    }
}
