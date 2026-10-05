@props(['icon' => 'history', 'time' => null, 'color' => 'slate'])

@php
    $palette = \App\Enums\Palette::fromName($color);
@endphp

<div {{ $attributes->class('flex justify-center') }}>
    <p @class(['inline-flex max-w-full flex-wrap items-center justify-center gap-1.5 rounded-full border px-3 py-1 text-xs', $palette->badgeClasses()])>
        <x-ui.icon :name="$icon" class="text-[14px]" />
        <span>{{ $slot }}</span>
        @if ($time)
            <span class="font-mono opacity-70">· {{ $time }}</span>
        @endif
    </p>
</div>
