<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketState;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\SupportTicketController;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/**
 * Everyone who can be given tickets (IT Support and Admin), with their workload.
 */
class TechnicianController extends Controller
{
    public function index(): View
    {
        $technicians = User::query()
            ->technicians()
            ->with(['roles', 'department'])
            ->withCount([
                'assignedTickets as active_count' => fn (Builder $query) => $query->inStates(...SupportTicketController::ACTIVE_STATES),
                'assignedTickets as overdue_count' => fn (Builder $query) => $query->overdue(),
                'assignedTickets as resolved_count' => fn (Builder $query) => $query
                    ->inStates(TicketState::Resolved, TicketState::Closed)
                    ->where('resolved_at', '>=', now()->subDays(30)),
            ])
            ->orderBy('name')
            ->get();

        return view('admin.technicians.index', [
            'technicians' => $technicians,
            'totals' => [
                'technicians' => $technicians->count(),
                'active' => $technicians->sum('active_count'),
                'overdue' => $technicians->sum('overdue_count'),
                'resolved' => $technicians->sum('resolved_count'),
            ],
        ]);
    }
}
