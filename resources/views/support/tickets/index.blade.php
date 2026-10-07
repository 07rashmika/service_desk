@php
    $sortUrl = function (string $column) use ($sort, $direction): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDirection, 'page' => null]);
    };
    $sortIcon = fn (string $column): string => $sort !== $column ? 'unfold_more' : ($direction === 'asc' ? 'arrow_upward' : 'arrow_downward');
    $currentTab = collect($tabs)->firstWhere('active', true);
@endphp

<x-layouts.app title="Ticket queue">
    <x-ui.page-header title="Ticket Queue" :description="$currentTab['label'].' · '.$tickets->total().' '.str('ticket')->plural($tickets->total())">
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('support.tickets.assigned')" icon="assignment_ind">My assigned</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tabs :items="$tabs" />

    <form method="GET" action="{{ url()->current() }}" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4">
        @if (request()->routeIs('support.tickets.index'))
            <input type="hidden" name="tab" value="{{ $activeTab }}">
        @endif
        <div class="flex flex-col gap-3 lg:flex-row">
            <div class="lg:flex-1">
                <label for="search" class="sr-only">Search tickets</label>
                <x-ui.input name="search" type="search" icon="search" :value="request('search')" placeholder="Search keyword or SD-000042" />
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button type="submit" variant="secondary" icon="filter_list">Apply</x-ui.button>
                @if ($hasFilters)
                    <x-ui.button variant="ghost" icon="close" :href="url()->current().(request()->routeIs('support.tickets.index') ? '?tab='.$activeTab : '')">Clear</x-ui.button>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            @foreach ([
                ['status', 'All statuses', $statuses->pluck('name', 'id')],
                ['priority', 'All priorities', $priorities->pluck('name', 'id')],
                ['category', 'All categories', $categories->pluck('name', 'id')],
                ['technician', 'Any technician', collect(['none' => 'Unassigned'])->union($technicians->pluck('name', 'id'))],
                ['period', 'Any time', collect($periods)->map(fn (array $period): string => $period['label'])],
            ] as [$name, $placeholder, $options])
                <div>
                    <label for="{{ $name }}" class="sr-only">{{ ucfirst($name) }}</label>
                    <x-ui.select :name="$name" onchange="this.form.requestSubmit()">
                        <option value="">{{ $placeholder }}</option>
                        @foreach ($options as $value => $label)
                            <option value="{{ $value }}" @selected((string) request($name) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            @endforeach
        </div>
    </form>

    @if ($tickets->isEmpty())
        <x-ui.card :padding="false">
            @if ($hasFilters)
                <x-ui.empty-state icon="search_off" title="No tickets match"
                    description="Nothing in this tab matches your search and filters.">
                    <x-ui.button variant="secondary" :href="url()->current().(request()->routeIs('support.tickets.index') ? '?tab='.$activeTab : '')">Clear filters</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state icon="task_alt" title="Nothing here" description="There are no tickets in this tab right now." />
            @endif
        </x-ui.card>
    @else
        <div x-data="{ selected: [], visible: @js($tickets->pluck('id')) }" class="flex flex-col gap-3">
            {{-- Bulk actions for the selected rows --}}
            <form id="bulk-form" method="POST" action="{{ route('support.tickets.bulk') }}"
                x-cloak x-show="selected.length" x-transition.opacity
                class="sticky top-[68px] z-20 flex flex-wrap items-center gap-3 rounded-lg bg-primary-700 px-4 py-3 text-white shadow-modal">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="tickets[]" x-bind:value="id">
                </template>
                <p class="text-label font-medium"><span x-text="selected.length"></span> selected</p>
                <button type="button" class="text-label underline opacity-80 hover:opacity-100" x-on:click="selected = []">Deselect all</button>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <button type="submit" name="action" value="take"
                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-white/15 px-3 text-label font-medium hover:bg-white/25">
                        <x-ui.icon name="front_hand" class="text-[16px]" /> Take selected
                    </button>
                    <label for="bulk-technician" class="sr-only">Assign to</label>
                    <select id="bulk-technician" name="technician_id" class="h-8 rounded-lg border-0 bg-white/15 pr-8 pl-3 text-label text-white [&>option]:text-slate-900">
                        <option value="">Assign to…</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" name="action" value="assign"
                        class="inline-flex h-8 items-center rounded-lg bg-white px-3 text-label font-medium text-primary-700 hover:bg-primary-50">Assign</button>
                    <span class="hidden h-5 w-px bg-white/30 sm:block"></span>
                    <label for="bulk-priority" class="sr-only">New priority</label>
                    <select id="bulk-priority" name="priority_id" class="h-8 rounded-lg border-0 bg-white/15 pr-8 pl-3 text-label text-white [&>option]:text-slate-900">
                        <option value="">Change priority…</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" name="action" value="priority"
                        class="inline-flex h-8 items-center rounded-lg bg-white px-3 text-label font-medium text-primary-700 hover:bg-primary-50">Apply</button>
                </div>
            </form>

            {{-- Phones: cards --}}
            <ul class="flex flex-col gap-3 md:hidden">
                @foreach ($tickets as $ticket)
                    <li class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between gap-2">
                            <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                            <x-ticket.sla :ticket="$ticket" />
                        </div>
                        <a href="{{ route('support.tickets.show', $ticket) }}" class="font-medium text-slate-900">{{ $ticket->title }}</a>
                        <p class="text-xs text-slate-500">{{ $ticket->creator->name }} · {{ $ticket->category->name }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ticket.status-badge :status="$ticket->status" />
                            <x-ticket.priority-badge :priority="$ticket->priority" />
                            <span class="ml-auto text-xs text-slate-500">{{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Tablets and up: table --}}
            <x-ui.table class="hidden md:block">
                <x-slot:head>
                    <x-ui.table.heading class="w-10">
                        <label class="sr-only" for="select-all">Select all tickets on this page</label>
                        <input id="select-all" type="checkbox" class="size-4 rounded border-slate-300 accent-primary-600"
                            x-bind:checked="selected.length === visible.length"
                            x-on:change="selected = $event.target.checked ? [...visible] : []">
                    </x-ui.table.heading>
                    <x-ui.table.heading>Ref</x-ui.table.heading>
                    <x-ui.table.heading>Title &amp; requester</x-ui.table.heading>
                    <x-ui.table.heading class="hidden 2xl:table-cell">Category</x-ui.table.heading>
                    <x-ui.table.heading>
                        <a href="{{ $sortUrl('priority') }}" class="inline-flex items-center gap-1 hover:text-slate-900">Priority <x-ui.icon :name="$sortIcon('priority')" class="text-[14px]" /></a>
                    </x-ui.table.heading>
                    <x-ui.table.heading>Status</x-ui.table.heading>
                    <x-ui.table.heading>Assigned to</x-ui.table.heading>
                    <x-ui.table.heading>
                        <a href="{{ $sortUrl('due') }}" class="inline-flex items-center gap-1 hover:text-slate-900">SLA <x-ui.icon :name="$sortIcon('due')" class="text-[14px]" /></a>
                    </x-ui.table.heading>
                    <x-ui.table.heading class="hidden 2xl:table-cell">
                        <a href="{{ $sortUrl('created') }}" class="inline-flex items-center gap-1 hover:text-slate-900">Created <x-ui.icon :name="$sortIcon('created')" class="text-[14px]" /></a>
                    </x-ui.table.heading>
                </x-slot:head>

                @foreach ($tickets as $ticket)
                    <tr class="transition-colors hover:bg-slate-50" x-bind:class="selected.includes({{ $ticket->id }}) && 'bg-primary-50/60'">
                        <x-ui.table.cell>
                            <label class="sr-only" for="select-{{ $ticket->id }}">Select {{ $ticket->reference }}</label>
                            <input id="select-{{ $ticket->id }}" type="checkbox" value="{{ $ticket->id }}" x-model.number="selected"
                                class="size-4 rounded border-slate-300 accent-primary-600">
                        </x-ui.table.cell>
                        <x-ui.table.cell><x-ui.ticket-ref :href="route('support.tickets.show', $ticket)">{{ $ticket->reference }}</x-ui.ticket-ref></x-ui.table.cell>
                        <x-ui.table.cell class="min-w-64">
                            <a href="{{ route('support.tickets.show', $ticket) }}" class="font-medium text-slate-900 hover:text-primary-700">{{ $ticket->title }}</a>
                            <p class="text-xs text-slate-500">
                                {{ $ticket->creator->name }}{{ $ticket->creator->department ? ' · '.$ticket->creator->department->name : '' }}<span class="2xl:hidden"> · {{ $ticket->category->name }}</span>
                            </p>
                        </x-ui.table.cell>
                        <x-ui.table.cell class="hidden whitespace-nowrap text-slate-600 2xl:table-cell">{{ $ticket->category->name }}</x-ui.table.cell>
                        <x-ui.table.cell><x-ticket.priority-badge :priority="$ticket->priority" /></x-ui.table.cell>
                        <x-ui.table.cell><x-ticket.status-badge :status="$ticket->status" /></x-ui.table.cell>
                        <x-ui.table.cell class="whitespace-nowrap">
                            @if ($ticket->assignee)
                                <span class="flex items-center gap-2"><x-ui.avatar :name="$ticket->assignee->name" size="sm" /> {{ $ticket->assignee->name }}</span>
                            @elseif (auth()->user()->can('take', $ticket))
                                <form method="POST" action="{{ route('support.tickets.take', $ticket) }}">
                                    @csrf
                                    <x-ui.button type="submit" size="sm" icon="front_hand">Take</x-ui.button>
                                </form>
                            @else
                                <span class="text-slate-400 italic">Unassigned</span>
                            @endif
                        </x-ui.table.cell>
                        <x-ui.table.cell class="whitespace-nowrap"><x-ticket.sla :ticket="$ticket" /></x-ui.table.cell>
                        <x-ui.table.cell class="hidden whitespace-nowrap text-slate-600 2xl:table-cell" title="{{ $ticket->created_at->toDayDateTimeString() }}">
                            {{ $ticket->created_at->diffForHumans() }}
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
        </div>
    @endif
</x-layouts.app>
