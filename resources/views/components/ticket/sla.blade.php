@props(['ticket'])

{{-- The SLA timer for a ticket: met or missed once resolved, paused while waiting on the requester. --}}
@php
    $state = $ticket->state();
    $isFinished = in_array($state, [\App\Enums\TicketState::Resolved, \App\Enums\TicketState::Closed], true);
    $metDeadline = $isFinished && $ticket->due_at && $ticket->resolved_at && $ticket->resolved_at->lte($ticket->due_at);
@endphp

<x-ui.sla-timer
    :due="$ticket->due_at"
    :met="$isFinished && ($metDeadline || ! $ticket->due_at)"
    :missed="$isFinished && $ticket->due_at && ! $metDeadline"
    :paused="$state === \App\Enums\TicketState::WaitingForUser"
    {{ $attributes }} />
