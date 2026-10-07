@props(['technicians', 'days'])

<x-ui.table>
    <x-slot:head>
        <x-ui.table.heading>Technician</x-ui.table.heading>
        <x-ui.table.heading class="text-right">Open now</x-ui.table.heading>
        <x-ui.table.heading class="text-right">Resolved ({{ $days }}d)</x-ui.table.heading>
        <x-ui.table.heading class="hidden text-right sm:table-cell">Avg. resolution</x-ui.table.heading>
        <x-ui.table.heading>Within SLA</x-ui.table.heading>
    </x-slot:head>
    @forelse ($technicians as $row)
        <tr class="transition-colors hover:bg-slate-50">
            <x-ui.table.cell>
                <span class="flex items-center gap-2">
                    <x-ui.avatar :name="$row['technician']->name" size="sm" />
                    <span class="font-medium text-slate-900">{{ $row['technician']->name }}</span>
                </span>
            </x-ui.table.cell>
            <x-ui.table.cell class="text-right font-mono text-xs tabular-nums text-slate-700">{{ $row['active'] }}</x-ui.table.cell>
            <x-ui.table.cell class="text-right font-mono text-xs tabular-nums text-slate-700">{{ $row['resolved'] }}</x-ui.table.cell>
            <x-ui.table.cell class="hidden text-right font-mono text-xs tabular-nums text-slate-700 sm:table-cell">
                {{ $row['averageResolutionHours'] !== null ? $row['averageResolutionHours'].'h' : '—' }}
            </x-ui.table.cell>
            <x-ui.table.cell><x-reports.meter :value="$row['compliance']" /></x-ui.table.cell>
        </tr>
    @empty
        <tr><x-ui.table.cell colspan="5" class="text-slate-500">No technicians yet.</x-ui.table.cell></tr>
    @endforelse
</x-ui.table>
