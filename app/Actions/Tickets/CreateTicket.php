<?php

namespace App\Actions\Tickets;

use App\Enums\TicketState;
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
     *
     * @param  array{title: string, description: string, category_id: int, priority_id: int}  $data
     * @param  array<int, UploadedFile>  $files
     */
    public function handle(User $requester, array $data, array $files = []): Ticket
    {
        $priority = TicketPriority::query()->findOrFail($data['priority_id']);

        return DB::transaction(function () use ($requester, $data, $files, $priority): Ticket {
            $ticket = Ticket::query()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'priority_id' => $priority->id,
                'status_id' => TicketStatus::for(TicketState::Open)->id,
                'created_by' => $requester->id,
                'due_at' => now()->addHours($priority->resolution_hours),
            ]);

            $this->storeAttachments->handle($ticket, $requester, $files);

            return $ticket;
        });
    }
}
