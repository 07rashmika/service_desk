@props(['align' => 'right', 'width' => 'w-56'])

<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <div x-on:click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-cloak x-show="open" x-transition.origin.top
        x-on:click="if ($event.target.closest('a, button')) open = false"
        @class([
            'absolute z-50 mt-2 rounded-lg border border-slate-200 bg-white py-1 shadow-popover',
            $width,
            'right-0' => $align === 'right',
            'left-0' => $align !== 'right',
        ])>
        {{ $slot }}
    </div>
</div>
