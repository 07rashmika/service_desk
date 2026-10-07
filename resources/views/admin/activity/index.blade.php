@php
    $eventColors = ['ticket' => 'primary', 'user' => 'blue', 'role' => 'violet', 'settings' => 'amber'];
    $formatValue = fn (mixed $value): string => match (true) {
        $value === null || $value === '' => '—',
        is_bool($value) => $value ? 'Yes' : 'No',
        is_array($value) => $value === [] ? '—' : implode(', ', $value),
        default => (string) $value,
    };
@endphp

<x-layouts.app title="Activity log">
    <x-ui.page-header eyebrow="Security" title="Activity Log"
        description="Every change to tickets, users, roles and settings: who made it, when, and what changed." />

    <form method="GET" action="{{ route('admin.activity.index') }}" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 lg:flex-row lg:items-center">
        <div class="lg:flex-1">
            <label for="search" class="sr-only">Search</label>
            <x-ui.input name="search" type="search" icon="search" :value="request('search')" placeholder="Search descriptions, e.g. SD-000042 or a name" />
        </div>
        <div class="grid grid-cols-3 gap-3 lg:flex">
            <div class="lg:w-44">
                <label for="user" class="sr-only">Person</label>
                <x-ui.select name="user" onchange="this.form.requestSubmit()">
                    <option value="">Anyone</option>
                    <option value="system" @selected(request('user') === 'system')>System (automatic)</option>
                    @foreach ($people as $person)
                        <option value="{{ $person->id }}" @selected(request('user') == $person->id)>{{ $person->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="lg:w-44">
                <label for="type" class="sr-only">Type</label>
                <x-ui.select name="type" onchange="this.form.requestSubmit()">
                    <option value="">All changes</option>
                    @foreach ($types as $prefix => $label)
                        <option value="{{ $prefix }}" @selected(request('type') === $prefix)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="lg:w-40">
                <label for="period" class="sr-only">When</label>
                <x-ui.select name="period" onchange="this.form.requestSubmit()">
                    <option value="">Any time</option>
                    @foreach ($periods as $days => $label)
                        <option value="{{ $days }}" @selected(request('period') == $days)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="secondary" icon="filter_list">Apply</x-ui.button>
            @if ($hasFilters)
                <x-ui.button variant="ghost" icon="close" :href="route('admin.activity.index')">Clear</x-ui.button>
            @endif
        </div>
    </form>

    @if ($activities->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state icon="history" title="No activity found" description="Nothing matches your filters." />
        </x-ui.card>
    @else
        <x-ui.table>
            <x-slot:head>
                <x-ui.table.heading class="w-40">When</x-ui.table.heading>
                <x-ui.table.heading class="w-48">Who</x-ui.table.heading>
                <x-ui.table.heading>What happened</x-ui.table.heading>
            </x-slot:head>
            @foreach ($activities as $activity)
                @php($changes = $activity->changes())
                <tr class="align-top transition-colors hover:bg-slate-50">
                    <x-ui.table.cell class="whitespace-nowrap">
                        <p class="text-slate-900">{{ $activity->created_at->format('M j, g:i A') }}</p>
                        <p class="text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p>
                    </x-ui.table.cell>
                    <x-ui.table.cell>
                        @if ($activity->user)
                            <span class="flex items-center gap-2"><x-ui.avatar :name="$activity->user->name" size="sm" /> {{ $activity->user->name }}</span>
                        @else
                            <span class="flex items-center gap-2 text-slate-500">
                                <span class="flex size-6 items-center justify-center rounded-full bg-slate-100"><x-ui.icon name="smart_toy" class="text-[14px]" /></span>
                                System
                            </span>
                        @endif
                    </x-ui.table.cell>
                    <x-ui.table.cell class="min-w-72">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($activity->subject instanceof \App\Models\Ticket)
                                <x-ui.ticket-ref :href="route('support.tickets.show', $activity->subject)">{{ $activity->subject->reference }}</x-ui.ticket-ref>
                            @endif
                            <span class="text-slate-900">{{ $activity->description }}</span>
                            <x-ui.badge :color="$eventColors[str($activity->event)->before('.')->toString()] ?? 'slate'" class="font-mono">{{ $activity->event }}</x-ui.badge>
                        </div>

                        @if ($changes !== [])
                            <details class="group mt-2">
                                <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700">
                                    <x-ui.icon name="chevron_right" class="text-[16px] transition-transform group-open:rotate-90" /> Show changes
                                </summary>
                                <dl class="mt-2 grid grid-cols-[auto_1fr_1fr] gap-x-3 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
                                    <dt class="font-medium text-slate-500">Field</dt>
                                    <dd class="font-medium text-slate-500">Before</dd>
                                    <dd class="font-medium text-slate-500">After</dd>
                                    @foreach ($changes as $field => $change)
                                        <dt class="text-slate-700">{{ str($field)->replace('_', ' ')->ucfirst() }}</dt>
                                        <dd class="rounded bg-red-50 px-1.5 py-0.5 break-words text-red-700">{{ $formatValue($change['old']) }}</dd>
                                        <dd class="rounded bg-emerald-50 px-1.5 py-0.5 break-words text-emerald-700">{{ $formatValue($change['new']) }}</dd>
                                    @endforeach
                                </dl>
                            </details>
                        @endif
                    </x-ui.table.cell>
                </tr>
            @endforeach

            @if ($activities->hasPages())
                <x-slot:footer>{{ $activities->links() }}</x-slot:footer>
            @endif
        </x-ui.table>
    @endif
</x-layouts.app>
