@props(['steps' => [], 'current' => 0, 'color' => 'primary'])

{{--
    Each step: ['label' => string, 'time' => ?string]. Steps before $current are done, unless
    they have no time, which means the ticket skipped them (e.g. it never waited on the user).
--}}
@php
    $palette = \App\Enums\Palette::fromName($color);
@endphp

<ol {{ $attributes->class('flex gap-2 overflow-x-auto') }}
    x-data x-init="$nextTick(() => { const step = $el.querySelector('[aria-current=step]'); if (step) $el.scrollLeft = step.offsetLeft - $el.offsetLeft - 16 })">
    @foreach ($steps as $index => $step)
        @php
            $state = match (true) {
                $index === $current => 'current',
                $index > $current => 'upcoming',
                empty($step['time']) => 'skipped',
                default => 'done',
            };
        @endphp
        <li @class([
            'flex min-w-36 flex-1 flex-col gap-1 rounded-lg border px-3 py-2.5',
            'border-slate-200 bg-white' => $state === 'done',
            $palette->badgeClasses() => $state === 'current',
            'border-dashed border-slate-200 bg-slate-50/60 text-slate-400' => in_array($state, ['upcoming', 'skipped'], true),
        ]) @if ($state === 'current') aria-current="step" @endif>
            <span class="flex items-center gap-2 text-label font-medium">
                @if ($state === 'done')
                    <x-ui.icon name="check" class="text-[16px] text-emerald-600" />
                    <span class="text-slate-900">{{ $step['label'] }}</span>
                @elseif ($state === 'current')
                    <x-ui.icon name="radio_button_checked" class="text-[16px]" />
                    {{ $step['label'] }}
                @elseif ($state === 'skipped')
                    <x-ui.icon name="remove" class="text-[16px]" />
                    {{ $step['label'] }}
                @else
                    <span class="flex size-4 items-center justify-center rounded-full border border-slate-300 text-[10px]">{{ $index + 1 }}</span>
                    {{ $step['label'] }}
                @endif
            </span>
            <span @class(['pl-6 font-mono text-xs', 'text-slate-400' => in_array($state, ['upcoming', 'skipped'], true), 'opacity-80' => in_array($state, ['done', 'current'], true)])>
                {{ $step['time'] ?? match ($state) { 'current' => 'Now', 'upcoming' => 'Upcoming', 'skipped' => 'Skipped', default => '' } }}
            </span>
        </li>
    @endforeach
</ol>
