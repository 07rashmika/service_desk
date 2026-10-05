@props(['label', 'value', 'unit' => null, 'icon' => null, 'tone' => 'default', 'href' => null])

@php
    $iconClasses = match ($tone) {
        'danger' => 'bg-red-50 text-red-600',
        'warning' => 'bg-orange-50 text-orange-600',
        'success' => 'bg-emerald-50 text-emerald-600',
        default => 'bg-primary-50 text-primary-600',
    };
    $valueClasses = match ($tone) {
        'danger' => 'text-red-600',
        default => 'text-slate-900',
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class(['flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-5', 'transition-colors hover:border-primary-200' => $href]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-label font-medium text-slate-600">{{ $label }}</p>
        @if ($icon)
            <span @class(['flex size-9 items-center justify-center rounded-lg', $iconClasses])>
                <x-ui.icon :name="$icon" />
            </span>
        @endif
    </div>
    <p class="flex items-baseline gap-1.5">
        <span @class(['text-3xl font-bold tracking-tight tabular-nums', $valueClasses])>{{ $value }}</span>
        @if ($unit)
            <span class="text-label text-slate-500">{{ $unit }}</span>
        @endif
    </p>
    @if ($slot->isNotEmpty())
        <div class="text-label text-slate-500">{{ $slot }}</div>
    @endif
</{{ $tag }}>
