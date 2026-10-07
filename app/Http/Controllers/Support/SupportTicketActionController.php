<?php

namespace App\Http\Controllers\Support;

use App\Actions\Tickets\AskRequesterForInfo;
use App\Actions\Tickets\AssignTicket;
use App\Actions\Tickets\ResolveTicket;
use App\Actions\Tickets\TakeTicket;
use App\Actions\Tickets\TransitionTicket;
use App\Actions\Tickets\UpdateTicketTriage;
use App\Enums\TicketState;
use App\Exceptions\TicketAlreadyTaken;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkTicketActionRequest;
use App\Http\Requests\ResolveTicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * What IT staff do with a ticket: take or assign it, work it, ask the requester, resolve it.
 */
class SupportTicketActionController extends Controller
{
    public function take(Ticket $ticket, Request $request, TakeTicket $takeTicket): RedirectResponse
    {
        Gate::authorize('viewQueue', Ticket::class);

        if ($request->user()->cannot('take', $ticket)) {
            return back()->with('error', "{$ticket->reference} has already been taken.");
        }

        try {
            $takeTicket->handle($ticket, $request->user());
        } catch (TicketAlreadyTaken $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', "You took {$ticket->reference}. Start work when you're ready.");
    }

    /**
     * Give the ticket to a technician (or pass it on), with an optional handoff note.
     */
    public function assign(Request $request, Ticket $ticket, AssignTicket $assignTicket): RedirectResponse
    {
        Gate::authorize('assign', $ticket);

        $validated = $request->validateWithBag('assignTicket', [
            'technician_id' => [
                'required',
                'integer',
                self::technicianRule(),
                Rule::notIn(array_filter([$ticket->assigned_to])),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'technician_id.required' => 'Choose a technician.',
            'technician_id.not_in' => 'This ticket is already assigned to them.',
        ]);

        $technician = User::query()->findOrFail($validated['technician_id']);
        $assignTicket->handle($ticket, $technician, $request->user(), $validated['note'] ?? null);

        $message = $technician->is($request->user())
            ? "You're now assigned to {$ticket->reference}."
            : "{$ticket->reference} is now assigned to {$technician->name}.";

        return redirect()->route('support.tickets.show', $ticket)->with('success', $message);
    }

    /**
     * Validation rule: the chosen user must be an active technician (IT Support or Admin).
     */
    public static function technicianRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! User::query()->technicians()->whereKey($value)->exists()) {
                $fail('Choose an active IT Support technician.');
            }
        };
    }

    public function start(Request $request, Ticket $ticket, TransitionTicket $transitionTicket): RedirectResponse
    {
        Gate::authorize('changeStatus', [$ticket, TicketState::InProgress]);

        $transitionTicket->handle($ticket, TicketState::InProgress, $request->user());

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', "{$ticket->reference} is now in progress.");
    }

    public function askRequester(Request $request, Ticket $ticket, AskRequesterForInfo $askRequester): RedirectResponse
    {
        Gate::authorize('changeStatus', [$ticket, TicketState::WaitingForUser]);

        $validated = $request->validateWithBag('askRequester', [
            'question' => ['required', 'string', 'max:5000'],
        ]);

        $askRequester->handle($ticket, $request->user(), $validated['question']);

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', "Question sent. The ticket is waiting for {$ticket->creator->name}'s reply.");
    }

    public function resolve(ResolveTicketRequest $request, Ticket $ticket, ResolveTicket $resolveTicket): RedirectResponse
    {
        $resolveTicket->handle($ticket, $request->validated('solution'), $request->user());

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', "{$ticket->reference} is resolved. The requester can now confirm the fix.");
    }

    public function triage(Request $request, Ticket $ticket, UpdateTicketTriage $updateTriage): RedirectResponse
    {
        Gate::authorize('triage', $ticket);

        $validated = $request->validateWithBag('triage', [
            'category_id' => ['required', 'integer', Rule::exists(TicketCategory::class, 'id')],
            'priority_id' => ['required', 'integer', Rule::exists(TicketPriority::class, 'id')],
        ]);

        $updateTriage->handle($ticket, (int) $validated['category_id'], (int) $validated['priority_id'], $request->user());

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('success', 'Ticket details updated.');
    }

    /**
     * Apply one action to the tickets selected in the queue. Tickets the action
     * doesn't apply to (e.g. already taken) are skipped and counted.
     */
    public function bulk(BulkTicketActionRequest $request, TakeTicket $takeTicket, AssignTicket $assignTicket, UpdateTicketTriage $updateTriage): RedirectResponse
    {
        $user = $request->user();
        $action = $request->validated('action');
        $tickets = Ticket::query()->with(['status', 'category', 'priority'])->findMany($request->validated('tickets'));
        $technician = $action === 'assign' ? User::query()->findOrFail($request->validated('technician_id')) : null;
        $updated = 0;

        foreach ($tickets as $ticket) {
            if ($action === 'assign') {
                if ($user->cannot('assign', $ticket) || $ticket->assigned_to === $technician->id) {
                    continue;
                }

                $assignTicket->handle($ticket, $technician, $user);
                $updated++;
            } elseif ($action === 'take') {
                if ($user->cannot('take', $ticket)) {
                    continue;
                }

                try {
                    $takeTicket->handle($ticket, $user);
                    $updated++;
                } catch (TicketAlreadyTaken) {
                    continue;
                }
            } elseif ($user->can('triage', $ticket)) {
                $updateTriage->handle($ticket, $ticket->category_id, (int) $request->validated('priority_id'), $user);
                $updated++;
            }
        }

        $skipped = $tickets->count() - $updated;
        $verb = match ($action) {
            'take' => 'Took',
            'assign' => "Assigned {$technician->name} to",
            default => 'Updated the priority of',
        };
        $message = "{$verb} {$updated} ".str('ticket')->plural($updated).'.';

        if ($skipped > 0) {
            $message .= " {$skipped} skipped because ".($skipped === 1 ? "it wasn't" : "they weren't").' eligible.';
        }

        return back()->with($updated > 0 ? 'success' : 'error', $message);
    }
}
