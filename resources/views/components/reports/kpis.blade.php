@props(['summary', 'previous', 'days'])

@php
    use App\Reports\TicketReport;

    $versus = "vs previous {$days} days";
    $createdDelta = TicketReport::delta($summary['created'], $previous['created'], higherIsBetter: true);
@endphp

{{-- Headline figures for the period, each compared with the period before it. --}}
<div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
    {{-- More tickets is neither good nor bad, so its change is shown without a colour judgement. --}}
    <x-ui.stat-card label="Tickets created" :value="number_format($summary['created'])" icon="confirmation_number"
        :delta="$createdDelta ? [...$createdDelta, 'tone' => 'neutral'] : null" :delta-label="$versus" />
    <x-ui.stat-card label="Tickets resolved" :value="number_format($summary['resolved'])" icon="task_alt"
        :delta="TicketReport::delta($summary['resolved'], $previous['resolved'], higherIsBetter: true)" :delta-label="$versus" />
    <x-ui.stat-card label="Avg. resolution time" :value="$summary['averageResolutionHours'] ?? '—'" :unit="$summary['averageResolutionHours'] !== null ? 'hrs' : null" icon="timer"
        :delta="TicketReport::delta($summary['averageResolutionHours'], $previous['averageResolutionHours'], higherIsBetter: false, unit: 'h')" :delta-label="$versus" />
    <x-ui.stat-card label="Resolved within SLA" :value="$summary['slaCompliance'] !== null ? $summary['slaCompliance'].'%' : '—'" icon="verified"
        :tone="$summary['slaCompliance'] !== null && $summary['slaCompliance'] < 75 ? 'danger' : 'default'"
        :delta="TicketReport::delta($summary['slaCompliance'], $previous['slaCompliance'], higherIsBetter: true, unit: ' pts')" :delta-label="$versus" />
</div>
