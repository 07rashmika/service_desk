@props(['steps' => [], 'current' => 0, 'color' => 'primary'])

{{-- Each step: ['label' => string, 'time' => ?string]. Steps before $current are done. --}}
@php
    $palette = \App\Enums\Palette::fromName($color);
@endphp

<ol {{ $attributes->class('flex gap-2 overflow-x-auto') }}>
    @foreach ($steps as $index => $step)
        @php
            $state = $index < $current ? 'done' : ($index === $current ? 'current' : 'upcoming');
        @endphp
        <li @class([
            'flex min-w-36 flex-1 flex-col gap-1 rounded-lg border px-3 py-2.5',
            'border-slate-200 bg-white' => $state === 'done',
            $palette->badgeClasses() => $state === 'current',
            'border-dashed border-slate-200 bg-slate-50/60 text-slate-400' => $state === 'upcoming',
        ]) @if ($state === 'current') aria-current="step" @endif>
            <span class="flex items-center gap-2 text-label font-medium">
                @if ($state === 'done')
                    <x-ui.icon name="check" class="text-[16px] text-emerald-600" />
                    <span class="text-slate-900">{{ $step['label'] }}</span>
                @elseif ($state === 'current')
                    <x-ui.icon name="radio_button_checked" class="text-[16px]" />
                    {{ $step['label'] }}
                @else
                    <span class="flex size-4 items-center justify-center rounded-full border border-slate-300 text-[10px]">{{ $index + 1 }}</span>
                    {{ $step['label'] }}
                @endif
            </span>
            <span class="pl-6 font-mono text-xs @if ($state === 'upcoming') text-slate-400 @else opacity-80 @endif">
                {{ $step['time'] ?? ($state === 'current' ? 'Now' : ($state === 'upcoming' ? 'Upcoming' : '')) }}
            </span>
        </li>
    @endforeach
</ol>
