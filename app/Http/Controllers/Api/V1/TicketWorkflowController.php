<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tickets\AddTicketComment;
use App\Actions\Tickets\AskRequesterForInfo;
use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\TakeTicket;
use App\Actions\Tickets\TransitionTicket;
use App\Actions\Tickets\UpdateTicketTriage;
use App\Enums\TicketState;
use App\Exceptions\TicketAlreadyTaken;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Support\SupportTicketActionController;
use App\Http\Requests\ResolveTicketRequest;
use App\Http\Resources\V1\TicketResource;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moving a ticket through the workflow. Every endpoint uses the same permission
 * rules and actions as the website, and returns the updated ticket.
 */
class TicketWorkflowController extends Controller
{
    public function take(Request $request, Ticket $ticket, TakeTicket $takeTicket): JsonResponse|TicketResource
    {
        Gate::authorize('viewQueue', Ticket::class);

        try {
            Gate::authorize('take', $ticket);
            $takeTicket->handle($ticket, $request->user());
        } catch (TicketAlreadyTaken $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return $this->ticket($request, $ticket);
    }

    public function assign(Request $request, Ticket $ticket, AssignTicket $assignTicket): TicketResource
    {
        Gate::authorize('assign', $ticket);

        $validated = $request->validate([
            'technician_id' => ['required', 'integer', SupportTicketActionController::technicianRule(), Rule::notIn(array_filter([$ticket->assigned_to]))],
            'note' => ['nullable', 'string', 'max:1000'],
        ], ['technician_id.not_in' => 'This ticket is already assigned to them.']);

        $assignTicket->handle($ticket, User::query()->findOrFail($validated['technician_id']), $request->user(), $validated['note'] ?? null);

        return $this->ticket($request, $ticket);
    }

    public function start(Request $request, Ticket $ticket, TransitionTicket $transitionTicket): TicketResource
    {
        Gate::authorize('changeStatus', [$ticket, TicketState::InProgress]);

        $transitionTicket->handle($ticket, TicketState::InProgress, $request->user());

        return $this->ticket($request, $ticket);
    }

    public function ask(Request $request, Ticket $ticket, AskRequesterForInfo $askRequester): TicketResource
    {
        Gate::authorize('changeStatus', [$ticket, TicketState::WaitingForUser]);

        $validated = $request->validate(['question' => ['required', 'string', 'max:5000']]);
        $askRequester->handle($ticket, $request->user(), $validated['question']);

        return $this->ticket($request, $ticket);
    }

    public function resolve(ResolveTicketRequest $request, Ticket $ticket, ResolveTicket $resolveTicket): TicketResource
    {
        $resolveTicket->handle($ticket, $request->validated('solution'), $request->user());

        return $this->ticket($request, $ticket);
    }

    public function triage(Request $request, Ticket $ticket, UpdateTicketTriage $updateTriage): TicketResource
    {
        Gate::authorize('triage', $ticket);

        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists(TicketCategory::class, 'id')],
            'priority_id' => ['required', 'integer', Rule::exists(TicketPriority::class, 'id')],
        ]);

        $updateTriage->handle($ticket->loadMissing(['category', 'priority']), (int) $validated['category_id'], (int) $validated['priority_id'], $request->user());

        return $this->ticket($request, $ticket);
    }

    public function close(Request $request, Ticket $ticket, TransitionTicket $transitionTicket): TicketResource
    {
        Gate::authorize('close', $ticket);

        $transitionTicket->handle($ticket, TicketState::Closed, $request->user());

        return $this->ticket($request, $ticket);
    }

    public function reopen(Request $request, Ticket $ticket, AddTicketComment $addComment, TransitionTicket $transitionTicket): TicketResource
    {
        Gate::authorize('reopen', $ticket);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        DB::transaction(function () use ($ticket, $request, $validated, $addComment, $transitionTicket): void {
            $addComment->handle($ticket, $request->user(), $validated['reason']);
            $transitionTicket->handle($ticket, TicketState::InProgress, $request->user());
        });

        return $this->ticket($request, $ticket);
    }

    protected function ticket(Request $request, Ticket $ticket): TicketResource
    {
        return new TicketResource(TicketController::loadForDisplay($request, $ticket->fresh()));
    }
}
