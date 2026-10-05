@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconTrailing' => null,
])

@php
    $classes = [
        'inline-flex shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-600 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
        match ($size) {
            'sm' => 'h-8 px-2.5 text-xs',
            default => 'h-9 px-3.5 text-label',
        },
        match ($variant) {
            'secondary' => 'border border-slate-200 bg-white text-slate-900 hover:bg-slate-50',
            'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
            'danger' => 'bg-red-600 text-white hover:bg-red-700',
            'success' => 'bg-emerald-600 text-white hover:bg-emerald-700',
            default => 'bg-primary-600 text-white hover:bg-primary-700',
        },
    ];
    $iconClass = $size === 'sm' ? 'text-[16px]' : 'text-[18px]';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
@endif
    @if ($icon)
        <x-ui.icon :name="$icon" :class="$iconClass" />
    @endif
    {{ $slot }}
    @if ($iconTrailing)
        <x-ui.icon :name="$iconTrailing" :class="$iconClass" />
    @endif
@if ($href)
    </a>
@else
    </button>
@endif
