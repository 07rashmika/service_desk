<?php

namespace App\Enums;

/**
 * The kinds of email a person can switch off on their profile. In-app
 * notifications (the bell) are always sent.
 */
enum NotificationPreference: string
{
    case TicketUpdates = 'ticket_updates';
    case Comments = 'comments';
    case Assignments = 'assignments';

    public function label(): string
    {
        return match ($this) {
            self::TicketUpdates => 'My tickets change',
            self::Comments => 'Someone replies',
            self::Assignments => 'A ticket is assigned to me',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TicketUpdates => 'A ticket you reported is resolved, or IT logs one for you.',
            self::Comments => 'New replies on tickets you reported or are working on.',
            self::Assignments => 'For IT staff: someone gives you a ticket, or reopens one of yours.',
        };
    }
}
