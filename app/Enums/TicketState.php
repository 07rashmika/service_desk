<?php

namespace App\Enums;

/**
 * The fixed steps of the ticket workflow.
 *
 * Each case matches the `slug` of a row in the `ticket_statuses` table. Admins may
 * rename a status or change its colour, but code always refers to it by this enum.
 */
enum TicketState: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingForUser = 'waiting_for_user';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * The default display name, used when seeding the statuses table.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::WaitingForUser => 'Waiting for User',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /**
     * The default badge colour, from DESIGN.md.
     */
    public function defaultColor(): Palette
    {
        return match ($this) {
            self::Open => Palette::Blue,
            self::Assigned => Palette::Violet,
            self::InProgress => Palette::Amber,
            self::WaitingForUser => Palette::Orange,
            self::Resolved => Palette::Emerald,
            self::Closed => Palette::Slate,
        };
    }

    /**
     * Position in the workflow, starting at 1.
     */
    public function sortOrder(): int
    {
        return array_search($this, self::cases(), true) + 1;
    }

    /**
     * Whether the ticket is finished and no more work happens on it.
     */
    public function isFinal(): bool
    {
        return $this === self::Closed;
    }
}
