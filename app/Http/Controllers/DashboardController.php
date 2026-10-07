<?php

namespace App\Http\Controllers;

use App\Enums\TicketState;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The employee dashboard: a summary of the user's own tickets, the ones
     * waiting on their reply, and their most recent tickets.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('department');
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
}
