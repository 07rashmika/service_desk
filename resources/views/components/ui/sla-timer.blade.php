@php
    $styles = [
        'none' => ['text-slate-400', null],
        'met' => ['border border-emerald-200 bg-emerald-50 text-emerald-700', 'check_circle'],
        'missed' => ['border border-red-200 bg-red-50 text-red-700', 'cancel'],
        'paused' => ['border border-slate-200 bg-slate-50 text-slate-500', 'pause_circle'],
        'ok' => ['border border-slate-200 bg-white text-slate-600', 'schedule'],
        'due-soon' => ['border border-red-200 bg-red-50 text-red-700', 'alarm'],
        'overdue' => ['bg-red-600 text-white', 'warning'],
    ];
    [$classes, $icon] = $styles[$state];

    // A running timer keeps counting down in the browser.
    $isLive = in_array($state, ['ok', 'due-soon', 'overdue'], true);
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 font-mono text-xs font-medium tabular-nums', $classes]) }}
    @if ($due) title="Due {{ $due->toDayDateTimeString() }}" @endif
    @if ($isLive)
        x-data="slaTimer({{ Js::from([
            'due' => $due->getTimestampMs(),
            'serverNow' => now()->getTimestampMs(),
            'state' => $state,
            'dueSoonMinutes' => \App\View\Components\Ui\SlaTimer::DUE_SOON_MINUTES,
            'styles' => Arr::only($styles, ['ok', 'due-soon', 'overdue']),
        ]) }})"
    @endif>
    @if ($isLive)
        <x-ui.icon :name="$icon" class="text-[14px]" x-text="icon" />
        <span x-text="label">{{ $label }}</span>
    @else
        @if ($icon)
            <x-ui.icon :name="$icon" class="text-[14px]" />
        @endif
        {{ $label }}
    @endif
</span>
