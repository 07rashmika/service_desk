<x-layouts.app title="Technicians">
    <x-ui.page-header eyebrow="IT team" title="Technicians"
        description="Everyone who can be given tickets: people with the IT Support or Admin role. To add a technician, give someone the IT Support role on the Users page.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="group" :href="route('admin.users.index', ['role' => 'support'])">Manage in Users</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-ui.stat-card label="Technicians" :value="$totals['technicians']" icon="engineering" />
        <x-ui.stat-card label="Open tickets assigned" :value="$totals['active']" icon="inbox" />
        <x-ui.stat-card label="Overdue" :value="$totals['overdue']" icon="warning" :tone="$totals['overdue'] > 0 ? 'danger' : 'success'" />
        <x-ui.stat-card label="Resolved, last 30 days" :value="$totals['resolved']" icon="task_alt" tone="success" />
    </div>

    @if ($technicians->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state icon="engineering" title="No technicians yet" description="Give someone the IT Support role to see them here." />
        </x-ui.card>
    @else
        <x-ui.table>
            <x-slot:head>
                <x-ui.table.heading>Technician</x-ui.table.heading>
                <x-ui.table.heading class="hidden md:table-cell">Role</x-ui.table.heading>
                <x-ui.table.heading>Open tickets</x-ui.table.heading>
                <x-ui.table.heading>Overdue</x-ui.table.heading>
                <x-ui.table.heading class="hidden sm:table-cell">Resolved (30 days)</x-ui.table.heading>
                <x-ui.table.heading class="hidden lg:table-cell">Last sign-in</x-ui.table.heading>
                <x-ui.table.heading class="w-12"><span class="sr-only">Queue</span></x-ui.table.heading>
            </x-slot:head>
            @foreach ($technicians as $technician)
                @php($loadColor = $technician->active_count >= 10 ? 'red' : ($technician->active_count >= 5 ? 'amber' : 'emerald'))
                <tr class="transition-colors hover:bg-slate-50">
                    <x-ui.table.cell>
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$technician->name" />
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">{{ $technician->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ collect([$technician->job_title, $technician->department?->name])->filter()->implode(' · ') }}</p>
                            </div>
                        </div>
                    </x-ui.table.cell>
                    <x-ui.table.cell class="hidden md:table-cell"><x-ui.badge :color="$technician->primaryRole() === \App\Enums\RoleName::Admin ? 'violet' : 'primary'">{{ $technician->primaryRole()?->label() }}</x-ui.badge></x-ui.table.cell>
                    <x-ui.table.cell><x-ui.badge :color="$loadColor" dot>{{ $technician->active_count }} open</x-ui.badge></x-ui.table.cell>
                    <x-ui.table.cell>
                        <span @class(['font-mono text-xs tabular-nums', 'font-semibold text-red-600' => $technician->overdue_count > 0, 'text-slate-500' => $technician->overdue_count === 0])>{{ $technician->overdue_count }}</span>
                    </x-ui.table.cell>
                    <x-ui.table.cell class="hidden font-mono text-xs tabular-nums text-slate-600 sm:table-cell">{{ $technician->resolved_count }}</x-ui.table.cell>
                    <x-ui.table.cell class="hidden whitespace-nowrap text-slate-600 lg:table-cell">{{ $technician->last_login_at?->diffForHumans() ?? 'Never' }}</x-ui.table.cell>
                    <x-ui.table.cell>
                        <a href="{{ route('support.tickets.index', ['tab' => 'open', 'technician' => $technician->id]) }}" title="View {{ $technician->name }}'s tickets"
                            class="inline-flex rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-primary-700">
                            <span class="sr-only">View {{ $technician->name }}'s tickets</span>
                            <x-ui.icon name="arrow_forward" />
                        </a>
                    </x-ui.table.cell>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-layouts.app>
