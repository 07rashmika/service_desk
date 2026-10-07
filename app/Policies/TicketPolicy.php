<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Whether the user can see a list of their own tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ViewOwnTickets);
    }

    /**
     * Staff with "view all" see every ticket; everyone else only their own.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can(PermissionName::ViewAllTickets)) {
            return true;
        }

        return $ticket->created_by === $user->id && $user->can(PermissionName::ViewOwnTickets);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::CreateTickets);
    }

    /**
     * IT staff can log a ticket for another person, e.g. after a phone call or walk-in.
     */
    public function createOnBehalf(User $user): bool
    {
        return $this->create($user) && $user->can(PermissionName::ViewAllTickets);
    }

    /**
     * Replies are allowed until the ticket is closed.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket)
            && $user->can(PermissionName::AddComments)
            && $ticket->state() !== TicketState::Closed;
    }

    /**
     * Whether the user can see internal notes between IT staff.
     */
    public function viewInternalNotes(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionName::AddInternalNotes) && $this->view($user, $ticket);
    }

    /**
     * The requester confirms a resolved ticket is fixed.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        return $this->isRequesterOfResolvedTicket($user, $ticket);
    }

    /**
     * The requester says a resolved ticket isn't actually fixed.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        return $this->isRequesterOfResolvedTicket($user, $ticket);
    }

    protected function isRequesterOfResolvedTicket(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by === $user->id && $ticket->state() === TicketState::Resolved;
    }

    /**
     * Whether the user can work the support queue (see every ticket, take tickets).
     */
    public function viewQueue(User $user): bool
    {
        return $user->can(PermissionName::ViewAllTickets);
    }

    /**
     * Technicians take unassigned, open tickets for themselves.
     */
    public function take(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionName::AssignTickets)
            && $ticket->assigned_to === null
            && $ticket->state() === TicketState::Open;
    }

    /**
     * IT staff can give an active ticket to a technician, or pass it on to another one.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionName::AssignTickets) && $ticket->isActive();
    }

    /**
     * Starting work, or pausing to ask the requester something, is up to the assigned technician.
     */
    public function changeStatus(User $user, Ticket $ticket, TicketState $to): bool
    {
        return $ticket->assigned_to === $user->id
            && $user->can(PermissionName::ChangeTicketStatus)
            && $ticket->state()->canTransitionTo($to);
    }

    /**
     * The assigned technician resolves the ticket with a solution.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        return $ticket->assigned_to === $user->id
            && $user->can(PermissionName::ResolveTickets)
            && $ticket->state()->canTransitionTo(TicketState::Resolved);
    }

    /**
     * IT staff can correct the category or priority while the ticket is still active.
     */
    public function triage(User $user, Ticket $ticket): bool
    {
        return $user->can(PermissionName::ChangeTicketStatus) && $ticket->isActive();
    }

    /**
     * Internal notes can be added by IT staff until the ticket is closed.
     */
    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $this->viewInternalNotes($user, $ticket) && $ticket->state() !== TicketState::Closed;
    }
}
