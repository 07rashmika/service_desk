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
}
