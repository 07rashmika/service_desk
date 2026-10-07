<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Enums\TicketState;
use App\Http\Controllers\Support\SupportTicketController;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use App\Reports\TicketReport;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admins get the overview, other IT staff the support dashboard, and everyone else
     * a summary of their own tickets.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('department');

        if ($user->hasRole(RoleName::Admin)) {
            return $this->adminDashboard($user);
        }

        if ($user->can('viewQueue', Ticket::class)) {
            return $this->supportDashboard($user);
        }

        return $this->employeeDashboard($user);
    }

    /**
     * The employee dashboard: a summary of the user's own tickets, the ones
     * waiting on their reply, and their most recent tickets.
     */
    protected function employeeDashboard(User $user): View
    {
        $myTickets = fn () => Ticket::query()->reportedBy($user);

        $waitingTickets = $myTickets()
            ->inStates(TicketState::WaitingForUser)
            ->with([
                'status',
                'priority',
                'category',
                'assignee',
                'comments' => fn ($query) => $query->visibleToRequester()
                    ->where('user_id', '!=', $user->id)
                    ->with('user')
                    ->latest()
                    ->orderByDesc('id'),
            ])
            ->oldest('updated_at')
            ->limit(3)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'stats' => [
                'open' => $myTickets()->inStates(TicketState::Open, TicketState::Assigned)->count(),
                'inProgress' => $myTickets()->inStates(TicketState::InProgress)->count(),
                'waiting' => $myTickets()->inStates(TicketState::WaitingForUser)->count(),
                'resolvedThisMonth' => $myTickets()->where('resolved_at', '>=', now()->startOfMonth())->count(),
            ],
            'waitingTickets' => $waitingTickets,
            'recentTickets' => $myTickets()
                ->with(['status', 'priority', 'category', 'assignee'])
                ->latest('updated_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'totalTickets' => $myTickets()->count(),
        ]);
    }

    /**
     * The support dashboard: the technician's workload, the unassigned queue,
     * and how many tickets they resolved each day this week.
     */
    protected function supportDashboard(User $user): View
    {
        $myActiveTickets = fn () => Ticket::query()
            ->inStates(...SupportTicketController::ACTIVE_STATES)
            ->where('assigned_to', $user->id);

        $resolvedPerDay = Ticket::query()
            ->where('assigned_to', $user->id)
            ->where('resolved_at', '>=', now()->subDays(6)->startOfDay())
            ->pluck('resolved_at')
            ->countBy(fn ($resolvedAt): string => $resolvedAt->toDateString());

        return view('support.dashboard', [
            'user' => $user,
            'stats' => [
                'unassigned' => Ticket::query()->inStates(TicketState::Open)->whereNull('assigned_to')->count(),
                'mine' => $myActiveTickets()->count(),
                'dueToday' => $myActiveTickets()
                    ->inStates(TicketState::Assigned, TicketState::InProgress)
                    ->whereBetween('due_at', [now(), now()->endOfDay()])
                    ->count(),
                'overdue' => Ticket::query()->overdue()->where('assigned_to', $user->id)->count(),
            ],
            'myTickets' => $myActiveTickets()
                ->with(['status', 'priority', 'category', 'creator'])
                ->orderByRaw('due_at is null')
                ->orderBy('due_at')
                ->limit(6)
                ->get(),
            'unassignedTickets' => Ticket::query()
                ->inStates(TicketState::Open)
                ->whereNull('assigned_to')
                ->with(['status', 'priority', 'category', 'creator.department'])
                ->orderByDesc(TicketPriority::query()->select('level')->whereColumn('ticket_priorities.id', 'tickets.priority_id'))
                ->oldest()
                ->limit(5)
                ->get(),
            'resolvedPerDay' => collect(range(6, 0))->map(fn (int $daysAgo): array => [
                'label' => now()->subDays($daysAgo)->format('D'),
                'date' => now()->subDays($daysAgo)->format('M j'),
                'count' => $resolvedPerDay->get(now()->subDays($daysAgo)->toDateString(), 0),
                'isToday' => $daysAgo === 0,
            ])->all(),
        ]);
    }

    /**
     * The admin overview: the last 30 days at a glance and what needs attention now.
     */
    protected function adminDashboard(User $user): View
    {
        $report = new TicketReport(30);

        return view('admin.dashboard', [
            'user' => $user,
            'report' => $report,
            'summary' => $report->summary(),
            'previousSummary' => $report->previous()->summary(),
            'volume' => $report->dailyVolume(),
            'byStatus' => $report->byStatus(),
            'technicians' => $report->technicians()->take(5),
            'attention' => [
                'overdue' => Ticket::query()->overdue()->count(),
                'unassigned' => Ticket::query()->inStates(TicketState::Open)->whereNull('assigned_to')->count(),
                'waiting' => Ticket::query()->inStates(TicketState::WaitingForUser)->count(),
            ],
        ]);
    }
}
