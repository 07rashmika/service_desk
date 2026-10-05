@props(['href' => null, 'as' => 'a', 'icon' => null, 'danger' => false])

@php
    $classes = [
        'flex w-full items-center gap-2 px-3 py-2 text-left text-label transition-colors',
        'text-red-600 hover:bg-red-50' => $danger,
        'text-slate-700 hover:bg-slate-50 hover:text-slate-900' => ! $danger,
    ];
@endphp

@if ($as === 'button')
    <button {{ $attributes->merge(['type' => 'button'])->class($classes) }}>
@else
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@endif
    @if ($icon)
        <x-ui.icon :name="$icon" class="text-[18px] text-current opacity-70" />
    @endif
    {{ $slot }}
@if ($as === 'button')
    </button>
@else
    </a>
@endif
