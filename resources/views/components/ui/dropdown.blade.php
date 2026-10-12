@props(['align' => 'right', 'width' => 'w-56'])

{{-- Keyboard: arrow down opens the menu and moves through it; Escape closes it and returns to the button. --}}
<div class="relative" x-data="{ open: false, button() { return this.$refs.trigger.querySelector('button, a') } }"
    x-on:click.outside="open = false"
    x-on:keydown.escape="if (open) { open = false; button()?.focus() }"
    x-effect="button()?.setAttribute('aria-expanded', open)">
    <div x-ref="trigger" x-on:click="open = ! open"
        x-on:keydown.down.prevent="open = true; $nextTick(() => $focus.within($refs.menu).first())">
        {{ $trigger }}
    </div>

    <div x-ref="menu" x-cloak x-show="open" x-transition.origin.top
        x-on:keydown.down.prevent="$focus.within($refs.menu).wrap().next()"
        x-on:keydown.up.prevent="$focus.within($refs.menu).wrap().previous()"
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
