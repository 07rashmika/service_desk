@php
    $greeting = match (true) {
        now()->hour < 12 => 'Good morning',
        now()->hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header eyebrow="Admin · Last 30 days" :title="$greeting.', '.str($user->name)->before(' ')"
        description="How the IT service desk is doing, and what needs attention right now.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="confirmation_number" :href="route('support.tickets.index')">Ticket queue</x-ui.button>
            <x-ui.button icon="monitoring" :href="route('admin.reports.index')">Full reports</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Needs attention now --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ([
            ['overdue', 'Overdue tickets', 'warning', $attention['overdue'] > 0 ? 'red' : 'emerald', route('support.tickets.index', ['tab' => 'overdue'])],
            ['unassigned', 'Waiting for a technician', 'move_to_inbox', $attention['unassigned'] > 0 ? 'amber' : 'emerald', route('support.tickets.index', ['tab' => 'unassigned'])],
            ['waiting', 'Waiting on the requester', 'hourglass_top', 'slate', route('support.tickets.index', ['status' => \App\Models\TicketStatus::for(\App\Enums\TicketState::WaitingForUser)->id])],
        ] as [$key, $label, $icon, $color, $href])
            <a href="{{ $href }}" class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3 transition-colors hover:border-primary-200">
                <span @class(['flex size-9 items-center justify-center rounded-lg', \App\Enums\Palette::fromName($color)->badgeClasses()])>
                    <x-ui.icon :name="$icon" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-label text-slate-600">{{ $label }}</span>
                    <span class="block text-xl font-semibold text-slate-900">{{ $attention[$key] }}</span>
                </span>
                <x-ui.icon name="arrow_forward" class="text-[18px] text-slate-400" />
            </a>
        @endforeach
    </div>

    <x-reports.kpis :summary="$summary" :previous="$previousSummary" :days="$report->days" />

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <x-reports.volume-chart :volume="$volume" :days="$report->days" class="xl:col-span-2" />
        <x-ui.card title="Tickets by status" description="Created in the last 30 days">
            <x-chart.bar-list :items="$byStatus->map(fn (array $row): array => ['badge' => $row['status'], 'count' => $row['count']])" />
        </x-ui.card>
    </div>

    <section class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">Technicians</h2>
            <a href="{{ route('admin.technicians.index') }}" class="text-label font-medium text-primary-600 hover:text-primary-700">All technicians</a>
        </div>
        <x-reports.technicians :technicians="$technicians" :days="$report->days" />
    </section>
</x-layouts.app>
