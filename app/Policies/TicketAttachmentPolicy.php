<?php

namespace App\Policies;

use App\Models\TicketAttachment;
use App\Models\User;

class TicketAttachmentPolicy
{
    /**
     * Anyone who can see the ticket can download its files, except files on
     * internal notes, which only IT staff can see.
     */
    public function view(User $user, TicketAttachment $attachment): bool
    {
        if (! $user->can('view', $attachment->ticket)) {
            return false;
        }

        if ($attachment->comment?->is_internal) {
            return $user->can('viewInternalNotes', $attachment->ticket);
        }

        return true;
    }
}
