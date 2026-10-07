@php
    $greeting = match (true) {
        now()->hour < 12 => 'Good morning',
        now()->hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header :eyebrow="$user->primaryRole()?->label()"
        :title="$greeting.', '.str($user->name)->before(' ')"
        description="Here's what's happening with your IT requests.">
        @can('create', \App\Models\Ticket::class)
            <x-slot:actions>
                <x-ui.button :href="route('tickets.create')" icon="add">Report an issue</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-ui.stat-card label="Open tickets" :value="$stats['open']" icon="confirmation_number" :href="route('tickets.index', ['tab' => 'open'])">
            Waiting to be picked up
        </x-ui.stat-card>
        <x-ui.stat-card label="In progress" :value="$stats['inProgress']" icon="autorenew" :href="route('tickets.index', ['tab' => 'in_progress'])">
            Being worked on
        </x-ui.stat-card>
        <x-ui.stat-card label="Waiting for your reply" :value="$stats['waiting']" icon="notification_important"
            :tone="$stats['waiting'] > 0 ? 'warning' : 'default'" :href="route('tickets.index', ['tab' => 'waiting'])">
            {{ $stats['waiting'] > 0 ? 'Your input is needed' : 'Nothing needed from you' }}
        </x-ui.stat-card>
        <x-ui.stat-card label="Resolved this month" :value="$stats['resolvedThisMonth']" icon="task_alt" tone="success"
            :href="route('tickets.index', ['tab' => 'resolved'])" />
    </div>

    @if ($waitingTickets->isNotEmpty())
        <section class="flex flex-col gap-3">
            <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900">
                <span class="size-2 rounded-full bg-orange-500"></span>
                Needs your attention
                <span class="rounded-full bg-orange-100 px-1.5 text-xs font-medium text-orange-700">{{ $stats['waiting'] }}</span>
            </h2>

            @foreach ($waitingTickets as $ticket)
                @php($latestMessage = $ticket->comments->first())
                <article class="flex flex-col gap-4 rounded-lg border border-orange-200 bg-white p-5">
                    <div class="flex flex-wrap items-center gap-2 text-label text-slate-500">
                        <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                        <x-ticket.status-badge :status="$ticket->status" />
                        <x-ticket.priority-badge :priority="$ticket->priority" />
                        <span>Updated {{ $ticket->updated_at->diffForHumans() }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-900">{{ $ticket->title }}</h3>

                    @if ($latestMessage)
                        <div class="flex gap-3 rounded-lg bg-slate-50 p-4">
                            <x-ui.avatar :name="$latestMessage->user->name" />
                            <div class="min-w-0">
                                <p class="text-label">
                                    <span class="font-medium text-slate-900">{{ $latestMessage->user->name }}</span>
                                    <span class="text-slate-500">· {{ $latestMessage->created_at->diffForHumans() }}</span>
                                </p>
                                <p class="mt-1 line-clamp-3 text-sm text-slate-700">“{{ $latestMessage->body }}”</p>
                            </div>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <x-ui.button :href="route('tickets.show', $ticket).'#reply'" icon="reply">Reply now</x-ui.button>
                        <x-ui.button variant="secondary" :href="route('tickets.show', $ticket)" icon="open_in_new">View ticket</x-ui.button>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Recent tickets</h2>
            @if ($totalTickets > 0)
                <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1 text-label font-medium text-primary-600 hover:text-primary-700">
                    View all {{ $totalTickets }} tickets <x-ui.icon name="arrow_forward" class="text-[16px]" />
                </a>
            @endif
        </div>

        @if ($recentTickets->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state icon="confirmation_number" title="No tickets yet"
                    description="When something isn't working, report it here and the IT team will pick it up.">
                    <x-ui.button :href="route('tickets.create')" icon="add">Report an issue</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <x-ui.table>
                <x-slot:head>
                    <x-ui.table.heading>Reference</x-ui.table.heading>
                    <x-ui.table.heading>Subject</x-ui.table.heading>
                    <x-ui.table.heading class="hidden md:table-cell">Category</x-ui.table.heading>
                    <x-ui.table.heading class="hidden sm:table-cell">Priority</x-ui.table.heading>
                    <x-ui.table.heading>Status</x-ui.table.heading>
                    <x-ui.table.heading class="hidden lg:table-cell">Assigned to</x-ui.table.heading>
                </x-slot:head>
                @foreach ($recentTickets as $ticket)
                    <tr class="relative transition-colors hover:bg-slate-50">
                        <x-ui.table.cell><x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref></x-ui.table.cell>
                        <x-ui.table.cell class="min-w-56">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-slate-900 after:absolute after:inset-0 hover:text-primary-700">{{ $ticket->title }}</a>
                            <p class="text-xs text-slate-500">Updated {{ $ticket->updated_at->diffForHumans() }}</p>
                        </x-ui.table.cell>
                        <x-ui.table.cell class="hidden whitespace-nowrap text-slate-600 md:table-cell">{{ $ticket->category->name }}</x-ui.table.cell>
                        <x-ui.table.cell class="hidden sm:table-cell"><x-ticket.priority-badge :priority="$ticket->priority" /></x-ui.table.cell>
                        <x-ui.table.cell><x-ticket.status-badge :status="$ticket->status" /></x-ui.table.cell>
                        <x-ui.table.cell class="hidden whitespace-nowrap lg:table-cell">
                            @if ($ticket->assignee)
                                <span class="flex items-center gap-2"><x-ui.avatar :name="$ticket->assignee->name" size="sm" /> {{ $ticket->assignee->name }}</span>
                            @else
                                <span class="text-slate-400 italic">Unassigned</span>
                            @endif
                        </x-ui.table.cell>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </section>
</x-layouts.app>
