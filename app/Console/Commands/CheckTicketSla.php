<?php

namespace App\Console\Commands;

use App\Actions\RecordActivity;
use App\Enums\RoleName;
use App\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Tickets\TicketDueSoon;
use App\Notifications\Tickets\TicketOverdue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Runs every few minutes: warns technicians before a deadline and alerts them
 * and the admins once it has passed. Each alert is sent once per deadline.
 */
#[Signature('tickets:check-sla')]
#[Description('Send "due soon" and "overdue" alerts for ticket SLA deadlines')]
class CheckTicketSla extends Command
{
    public function handle(RecordActivity $recordActivity): int
    {
        $warned = 0;
        $breached = 0;

        Ticket::query()
            ->inStates(TicketState::Assigned, TicketState::InProgress)
            ->whereNotNull('assigned_to')
            ->whereNull('sla_warning_sent_at')
            ->whereBetween('due_at', [now(), now()->addMinutes(config('servicedesk.sla_warning_minutes'))])
            ->with(['assignee', 'priority'])
            ->each(function (Ticket $ticket) use (&$warned): void {
                $ticket->assignee->notify(new TicketDueSoon($ticket));
                $ticket->forceFill(['sla_warning_sent_at' => now()])->saveQuietly();
                $warned++;
            });

        $admins = User::query()->role(RoleName::Admin->value)->where('is_active', true)->get();

        Ticket::query()
            ->overdue()
            ->whereNull('sla_breach_sent_at')
            ->with(['assignee', 'priority'])
            ->each(function (Ticket $ticket) use ($admins, $recordActivity, &$breached): void {
                $recipients = $admins->concat([$ticket->assignee])->filter()->unique('id');
                Notification::send($recipients, new TicketOverdue($ticket));

                $ticket->forceFill(['sla_breach_sent_at' => now()])->saveQuietly();
                $recordActivity->handle(null, 'ticket.sla_breached', "Missed the {$ticket->priority->name} SLA deadline", $ticket);
                $breached++;
            });

        $this->components->info("Sent {$warned} due-soon and {$breached} overdue alerts.");

        return self::SUCCESS;
    }
}
