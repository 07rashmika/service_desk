@props(['type' => 'info', 'title' => null, 'dismissible' => false])

@php
    [$classes, $icon] = match ($type) {
        'success' => ['border-emerald-200 bg-emerald-50 text-emerald-800', 'check_circle'],
        'warning' => ['border-orange-200 bg-orange-50 text-orange-800', 'warning'],
        'error' => ['border-red-200 bg-red-50 text-red-800', 'error'],
        default => ['border-blue-200 bg-blue-50 text-blue-800', 'info'],
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ open: true }" x-show="open" x-transition.opacity @endif
    {{ $attributes->class(['flex gap-3 rounded-lg border p-3 text-label', $classes]) }}>
    <x-ui.icon :name="$icon" class="icon-filled text-[18px]" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" class="-m-1 rounded p-1 opacity-70 hover:opacity-100" x-on:click="open = false">
            <span class="sr-only">Dismiss</span>
            <x-ui.icon name="close" class="text-[18px]" />
        </button>
    @endif
</div>
