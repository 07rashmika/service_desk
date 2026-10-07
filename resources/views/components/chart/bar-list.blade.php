@props(['items', 'emptyText' => 'No tickets in this period.'])

{{--
    Horizontal bars for one series (counts per category, department…). One colour for every
    bar, value at the tip. Each item: ['label' => string, 'count' => int, 'badge' => ?TicketStatus].
--}}
@php
    $max = max(1, collect($items)->max('count'));
    $total = max(1, collect($items)->sum('count'));
@endphp

@if (collect($items)->sum('count') === 0)
    <p class="py-6 text-center text-label text-slate-500">{{ $emptyText }}</p>
@else
    <ul {{ $attributes->class('flex flex-col gap-3.5') }}>
        @foreach ($items as $item)
            {{-- Label and value share a line; the bar sits underneath at full width, so it reads at any card size. --}}
            <li class="flex flex-col gap-1.5" title="{{ $item['label'] ?? $item['badge']->name }}: {{ $item['count'] }} ({{ round($item['count'] / $total * 100) }}%)">
                <span class="flex items-center justify-between gap-3">
                    <span class="min-w-0 truncate text-label text-slate-700">
                        @isset($item['badge'])
                            <x-ticket.status-badge :status="$item['badge']" />
                        @else
                            {{ $item['label'] }}
                        @endisset
                    </span>
                    <span class="shrink-0 font-mono text-xs tabular-nums text-slate-700">
                        {{ $item['count'] }} <span class="text-slate-400">· {{ round($item['count'] / $total * 100) }}%</span>
                    </span>
                </span>
                <span class="block h-2.5 w-full">
                    <span class="block h-full rounded-r bg-primary-600" style="width: {{ $item['count'] / $max * 100 }}%"></span>
                </span>
            </li>
        @endforeach
    </ul>
@endif
