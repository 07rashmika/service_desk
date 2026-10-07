@php
    $greeting = match (true) {
        now()->hour < 12 => 'Good morning',
        now()->hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
    $maxResolved = max(1, collect($resolvedPerDay)->max('count'));
    $resolvedThisWeek = collect($resolvedPerDay)->sum('count');
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header :eyebrow="$user->primaryRole()?->label().' · Technician view'"
        :title="$greeting.', '.str($user->name)->before(' ')"
        description="Your workload and the tickets waiting to be picked up.">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('support.tickets.assigned')" icon="assignment_ind">My assigned</x-ui.button>
            <x-ui.button :href="route('support.tickets.index')" icon="confirmation_number">Open ticket queue</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-ui.stat-card label="Unassigned tickets" :value="$stats['unassigned']" icon="move_to_inbox"
            :href="route('support.tickets.index', ['tab' => 'unassigned'])">
            Waiting to be taken
        </x-ui.stat-card>
        <x-ui.stat-card label="Assigned to me" :value="$stats['mine']" icon="person" :href="route('support.tickets.assigned')">
            Active tickets
        </x-ui.stat-card>
        <x-ui.stat-card label="Due today" :value="$stats['dueToday']" icon="schedule"
            :tone="$stats['dueToday'] > 0 ? 'warning' : 'default'" :href="route('support.tickets.assigned', ['sort' => 'due'])">
            Of your tickets
        </x-ui.stat-card>
        <x-ui.stat-card label="My overdue" :value="$stats['overdue']" icon="warning"
            :tone="$stats['overdue'] > 0 ? 'danger' : 'success'" :href="route('support.tickets.index', ['tab' => 'overdue', 'technician' => $user->id])">
            {{ $stats['overdue'] > 0 ? 'Past the SLA deadline' : 'None of yours are overdue' }}
        </x-ui.stat-card>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-5">
        <x-ui.card title="My active tickets" description="Most urgent deadline first" :padding="false" class="xl:col-span-3">
            <x-slot:actions>
                <a href="{{ route('support.tickets.assigned') }}" class="text-label font-medium text-primary-600 hover:text-primary-700">View all</a>
            </x-slot:actions>

            @forelse ($myTickets as $ticket)
                <a href="{{ route('support.tickets.show', $ticket) }}"
                    @class(['flex flex-col gap-2 border-b border-slate-100 px-5 py-3.5 last:border-b-0 hover:bg-slate-50 sm:flex-row sm:items-center sm:gap-4', 'bg-red-50/50' => $ticket->due_at?->isPast() && $ticket->state() !== \App\Enums\TicketState::WaitingForUser])>
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                        <div class="min-w-0">
                            <p class="truncate font-medium text-slate-900">{{ $ticket->title }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $ticket->creator->name }} · {{ $ticket->category->name }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center gap-2 pl-[5.5rem] sm:pl-0">
                        <x-ticket.priority-badge :priority="$ticket->priority" />
                        <x-ticket.status-badge :status="$ticket->status" />
                        <x-ticket.sla :ticket="$ticket" />
                    </div>
                </a>
            @empty
                <x-ui.empty-state icon="task_alt" title="Nothing assigned to you"
                    description="Take a ticket from the unassigned queue to get started." />
            @endforelse
        </x-ui.card>

        <div class="flex flex-col gap-6 xl:col-span-2">
            <x-ui.card title="Unassigned queue" :description="$stats['unassigned'].' '.str('ticket')->plural($stats['unassigned']).' waiting, most urgent first'" :padding="false">
                @forelse ($unassignedTickets as $ticket)
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3.5 last:border-b-0">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2">
                                <x-ui.ticket-ref :href="route('support.tickets.show', $ticket)">{{ $ticket->reference }}</x-ui.ticket-ref>
                                <x-ticket.priority-badge :priority="$ticket->priority" />
                            </p>
                            <a href="{{ route('support.tickets.show', $ticket) }}" class="mt-1 block truncate text-label font-medium text-slate-900 hover:text-primary-700">{{ $ticket->title }}</a>
                            <p class="truncate text-xs text-slate-500">{{ $ticket->creator->name }}{{ $ticket->creator->department ? ' · '.$ticket->creator->department->name : '' }} · {{ $ticket->created_at->diffForHumans() }}</p>
                        </div>
                        @can('take', $ticket)
                            <form method="POST" action="{{ route('support.tickets.take', $ticket) }}">
                                @csrf
                                <x-ui.button type="submit" size="sm">Take</x-ui.button>
                            </form>
                        @endcan
                    </div>
                @empty
                    <x-ui.empty-state icon="inbox" title="Queue is empty" description="Every ticket has been picked up." />
                @endforelse
            </x-ui.card>

            <x-ui.card title="My resolved tickets" :description="$resolvedThisWeek.' in the last 7 days'">
                <div class="flex h-36 items-end gap-2" role="img" aria-label="Tickets you resolved per day over the last 7 days">
                    @foreach ($resolvedPerDay as $day)
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5" title="{{ $day['date'] }}: {{ $day['count'] }} resolved">
                            <span class="font-mono text-xs tabular-nums text-slate-500">{{ $day['count'] }}</span>
                            <span class="flex w-full flex-1 items-end">
                                <span @class(['w-full rounded-t', 'bg-primary-600' => $day['isToday'], 'bg-primary-200' => ! $day['isToday']])
                                    style="height: {{ max(4, round($day['count'] / $maxResolved * 100)) }}%"></span>
                            </span>
                            <span @class(['text-xs', 'font-semibold text-primary-700' => $day['isToday'], 'text-slate-500' => ! $day['isToday']])>{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
