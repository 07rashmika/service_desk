@props(['color' => 'slate', 'dot' => false, 'pulse' => false])

@php
    $palette = \App\Enums\Palette::fromName($color);
@endphp

<span {{ $attributes->class(['inline-flex h-[22px] items-center gap-1.5 whitespace-nowrap rounded-full border px-2 text-xs font-medium', $palette->badgeClasses()]) }}>
    @if ($dot || $pulse)
        <span class="relative flex size-1.5">
            @if ($pulse)
                <span @class(['absolute inline-flex size-full animate-ping rounded-full opacity-75', $palette->dotClass()])></span>
            @endif
            <span @class(['relative inline-flex size-1.5 rounded-full', $palette->dotClass()])></span>
        </span>
    @endif
    {{ $slot }}
</span>
