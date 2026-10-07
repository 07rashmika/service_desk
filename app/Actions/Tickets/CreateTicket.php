<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
use App\Events\TicketCreated;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateTicket
{
    public function __construct(private StoreTicketAttachments $storeAttachments) {}

    /**
     * Open a new ticket for the requester. The SLA deadline comes from the priority.
     * When IT staff log it for someone else, they're recorded as $loggedBy.
     *
     * @param  array{title: string, description: string, category_id: int, priority_id: int}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function handle(User $requester, array $data, array $files = [], ?User $loggedBy = null): Ticket
    {
        $priority = TicketPriority::query()->findOrFail($data['priority_id']);
        $loggedBy = $loggedBy?->is($requester) ? null : $loggedBy;

        return DB::transaction(function () use ($requester, $data, $files, $priority, $loggedBy): Ticket {
            $ticket = Ticket::query()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'priority_id' => $priority->id,
                'status_id' => TicketStatus::for(TicketState::Open)->id,
                'created_by' => $requester->id,
                'logged_by' => $loggedBy?->id,
                'due_at' => now()->addHours($priority->resolution_hours),
            ]);

            $this->storeAttachments->handle($ticket, $loggedBy ?? $requester, $files);

            TicketCreated::dispatch($ticket);

            return $ticket;
        });
    }
}
