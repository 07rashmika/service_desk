<x-layouts.app title="My tickets">
    <x-ui.page-header title="My Tickets" description="Track, follow up and reply to the IT issues you've reported.">
        <x-slot:meta>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">{{ $tabs[0]['count'] }} total</span>
        </x-slot:meta>
        <x-slot:actions>
            <x-ui.button :href="route('tickets.create')" icon="add">New Ticket</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-ui.stat-card label="Open" :value="$stats['open']" icon="inbox" />
        <x-ui.stat-card label="Waiting for you" :value="$stats['waiting']" icon="notification_important"
            :tone="$stats['waiting'] > 0 ? 'warning' : 'default'" :href="route('tickets.index', ['tab' => 'waiting'])" />
        <x-ui.stat-card label="In progress" :value="$stats['inProgress']" icon="autorenew" />
        <x-ui.stat-card label="Avg. resolution time" :value="$stats['averageResolutionHours'] ?? '—'"
            :unit="$stats['averageResolutionHours'] !== null ? 'hrs' : null" icon="speed" tone="success" />
    </div>

    <div class="flex flex-col gap-4">
        <form method="GET" action="{{ route('tickets.index') }}"
            class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 lg:flex-row lg:items-center">
            @if (request('tab'))
                <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif

            <div class="lg:flex-1">
                <label for="search" class="sr-only">Search tickets</label>
                <x-ui.input name="search" type="search" icon="search" :value="request('search')"
                    placeholder="Search keyword or SD-000042" />
            </div>
            <div class="grid grid-cols-2 gap-3 lg:flex">
                <div class="lg:w-48">
                    <label for="category" class="sr-only">Category</label>
                    <x-ui.select name="category" onchange="this.form.requestSubmit()">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="lg:w-44">
                    <label for="priority" class="sr-only">Priority</label>
                    <x-ui.select name="priority" onchange="this.form.requestSubmit()">
                        <option value="">All priorities</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->id }}" @selected(request('priority') == $priority->id)>{{ $priority->name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="secondary" icon="filter_list">Apply</x-ui.button>
                @if ($hasFilters)
                    <x-ui.button variant="ghost" icon="close" :href="route('tickets.index', request()->only('tab'))">Clear</x-ui.button>
                @endif
            </div>
        </form>

        <x-ui.tabs :items="$tabs" />

        @if ($tickets->isEmpty())
            <x-ui.card :padding="false">
                @if ($hasFilters || request('tab'))
                    <x-ui.empty-state icon="search_off" title="No tickets found"
                        description="No tickets match your search and filters. Try different keywords or clear the filters.">
                        <x-ui.button variant="secondary" :href="route('tickets.index')">Clear all filters</x-ui.button>
                    </x-ui.empty-state>
                @else
                    <x-ui.empty-state icon="confirmation_number" title="No tickets yet"
                        description="When something isn't working, report it here and the IT team will pick it up.">
                        <x-ui.button :href="route('tickets.create')" icon="add">Report an issue</x-ui.button>
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        @else
            {{-- Phones: stacked cards --}}
            <ul class="flex flex-col gap-3 md:hidden">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="{{ route('tickets.show', $ticket) }}" class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-4 hover:border-primary-200">
                            <div class="flex items-center justify-between gap-2">
                                <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                                <span class="text-xs text-slate-500">{{ $ticket->updated_at->diffForHumans() }}</span>
                            </div>
                            <p class="font-medium text-slate-900">{{ $ticket->title }}</p>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ticket.status-badge :status="$ticket->status" />
                                <x-ticket.priority-badge :priority="$ticket->priority" />
                                <span class="text-xs text-slate-500">{{ $ticket->category->name }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            {{-- Tablets and up: table --}}
            <x-ui.table class="hidden md:block">
                <x-slot:head>
                    <x-ui.table.heading>Reference</x-ui.table.heading>
                    <x-ui.table.heading>Subject &amp; category</x-ui.table.heading>
                    <x-ui.table.heading>Priority</x-ui.table.heading>
                    <x-ui.table.heading>Status</x-ui.table.heading>
                    <x-ui.table.heading>Assigned to</x-ui.table.heading>
                    <x-ui.table.heading>Created</x-ui.table.heading>
                </x-slot:head>

                @foreach ($tickets as $ticket)
                    <tr class="relative transition-colors hover:bg-slate-50">
                        <x-ui.table.cell><x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref></x-ui.table.cell>
                        <x-ui.table.cell class="min-w-64">
                            <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-slate-900 after:absolute after:inset-0 hover:text-primary-700">
                                {{ $ticket->title }}
                            </a>
                            <p class="flex items-center gap-1.5 text-xs text-slate-500">
                                {{ $ticket->category->name }}
                                @if ($ticket->state() === \App\Enums\TicketState::WaitingForUser)
                                    · <span class="inline-flex items-center gap-0.5 font-medium text-orange-700"><x-ui.icon name="reply" class="text-[14px]" /> Reply needed</span>
                                @endif
                            </p>
                        </x-ui.table.cell>
                        <x-ui.table.cell><x-ticket.priority-badge :priority="$ticket->priority" /></x-ui.table.cell>
                        <x-ui.table.cell><x-ticket.status-badge :status="$ticket->status" /></x-ui.table.cell>
                        <x-ui.table.cell class="whitespace-nowrap">
                            @if ($ticket->assignee)
                                <span class="flex items-center gap-2"><x-ui.avatar :name="$ticket->assignee->name" size="sm" /> {{ $ticket->assignee->name }}</span>
                            @else
                                <span class="text-slate-400 italic">Unassigned</span>
                            @endif
                        </x-ui.table.cell>
                        <x-ui.table.cell class="whitespace-nowrap text-slate-600" title="{{ $ticket->created_at->toDayDateTimeString() }}">
                            {{ $ticket->created_at->format('M j, Y') }}
                        </x-ui.table.cell>
                    </tr>
                @endforeach

                @if ($tickets->hasPages())
                    <x-slot:footer>{{ $tickets->links() }}</x-slot:footer>
                @endif
            </x-ui.table>

            @if ($tickets->hasPages())
                <div class="md:hidden">{{ $tickets->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.app>
