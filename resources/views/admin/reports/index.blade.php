<x-layouts.app title="Reports">
    <x-ui.page-header eyebrow="Insights" title="Reports"
        :description="'Ticket volume, SLA performance and workload from '.$report->from->format('M j').' to '.$report->to->format('M j, Y').'.'">
        @can(\App\Enums\PermissionName::ExportReports)
            <x-slot:actions>
                <x-ui.button variant="secondary" icon="download" :href="route('admin.reports.export', ['period' => $report->days])">Export CSV</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    {{-- One filter row above everything it scopes. --}}
    <x-ui.tabs :items="collect($periods)->map(fn (string $label, string $days): array => [
        'label' => $label,
        'href' => route('admin.reports.index', ['period' => $days]),
        'active' => (int) $days === $report->days,
    ])->values()->all()" />

    <x-reports.kpis :summary="$summary" :previous="$previousSummary" :days="$report->days" />

    <x-reports.volume-chart :volume="$volume" :days="$report->days" />

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        <x-ui.card title="Tickets by status" description="Tickets created in this period, by where they are now">
            <x-chart.bar-list :items="$byStatus->map(fn (array $row): array => ['badge' => $row['status'], 'count' => $row['count']])" />
        </x-ui.card>
        <x-ui.card title="Tickets by category" description="What people reported">
            <x-chart.bar-list :items="$byCategory" />
        </x-ui.card>
        <x-ui.card title="Tickets by department" description="Where requests came from">
            <x-chart.bar-list :items="$byDepartment" />
        </x-ui.card>
        <x-ui.card title="SLA by priority" description="Tickets resolved in this period" :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-xs font-medium tracking-wider text-slate-600 uppercase">
                        <tr>
                            <th scope="col" class="px-5 py-2.5">Priority</th>
                            <th scope="col" class="px-5 py-2.5 text-right">Resolved</th>
                            <th scope="col" class="px-5 py-2.5 text-right">Avg.</th>
                            <th scope="col" class="px-5 py-2.5 whitespace-nowrap">Within SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($slaByPriority as $row)
                            <tr>
                                <td class="px-5 py-3">
                                    <x-ticket.priority-badge :priority="$row['priority']" />
                                    <span class="mt-0.5 block text-xs text-slate-500">{{ $row['priority']->resolution_hours }}h target</span>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-xs tabular-nums text-slate-700">{{ $row['resolved'] }}</td>
                                <td class="px-5 py-3 text-right font-mono text-xs tabular-nums text-slate-700">{{ $row['averageResolutionHours'] !== null ? $row['averageResolutionHours'].'h' : '—' }}</td>
                                <td class="px-5 py-3"><x-reports.meter :value="$row['compliance']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <section class="flex flex-col gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Technician performance</h2>
            <p class="text-label text-slate-500">First responses met their target {{ $summary['firstResponseCompliance'] !== null ? $summary['firstResponseCompliance'].'% of the time' : '—' }} across the team.</p>
        </div>
        <x-reports.technicians :technicians="$technicians" :days="$report->days" />
    </section>
</x-layouts.app>
