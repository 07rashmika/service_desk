@props(['value'])

{{-- A percentage against 100%: indigo when healthy, amber when slipping, red when poor. The number is always shown. --}}
@if ($value === null)
    <span class="text-xs text-slate-400">—</span>
@else
    <span class="flex items-center gap-2 whitespace-nowrap" title="{{ $value }}% resolved within the SLA deadline">
        <span class="h-1.5 w-14 shrink-0 overflow-hidden rounded-full bg-primary-100 sm:w-20">
            <span @class([
                'block h-full rounded-full',
                'bg-primary-600' => $value >= 90,
                'bg-amber-500' => $value >= 75 && $value < 90,
                'bg-red-500' => $value < 75,
            ]) style="width: {{ $value }}%"></span>
        </span>
        <span class="font-mono text-xs tabular-nums text-slate-700">{{ $value }}%</span>
        @if ($value < 75)
            <x-ui.icon name="error" class="text-[14px] text-red-500" />
        @endif
    </span>
@endif
