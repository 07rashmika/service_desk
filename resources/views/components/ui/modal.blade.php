@props([
    'name',
    'title' => null,
    'description' => null,
    'icon' => null,
    'maxWidth' => 'lg',
    'show' => false,
])

@php
    $widthClass = match ($maxWidth) {
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        default => 'sm:max-w-lg',
    };
@endphp

{{-- Open with $dispatch('open-modal', '{{ $name }}') and close with $dispatch('close-modal', '{{ $name }}'). --}}
<div x-data="{ show: @js($show), name: @js($name) }"
    x-on:open-modal.window="if ($event.detail === name) show = true"
    x-on:close-modal.window="if ($event.detail === name) show = false"
    x-on:keydown.escape.window="show = false"
    x-effect="document.body.classList.toggle('overflow-hidden', show)"
    x-show="show" x-cloak
    class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true"
    @if ($title) aria-labelledby="{{ $name }}-title" @endif>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-slate-900/50" x-on:click="show = false"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-show="show"
            x-transition:enter="duration-200 ease-out" x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95" x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="duration-150 ease-in" x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 sm:scale-95"
            {{ $attributes->class(['relative w-full rounded-lg bg-white shadow-modal transition', $widthClass]) }}>
            @if ($title)
                <header class="flex items-start gap-3 border-b border-slate-200 px-6 py-4">
                    @if ($icon)
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <x-ui.icon :name="$icon" />
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <h2 id="{{ $name }}-title" class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                        @if ($description)
                            <p class="mt-0.5 text-label text-slate-500">{{ $description }}</p>
                        @endif
                    </div>
                    <button type="button" class="-m-1 rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" x-on:click="show = false">
                        <span class="sr-only">Close</span>
                        <x-ui.icon name="close" />
                    </button>
                </header>
            @endif

            <div class="px-6 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <footer class="flex flex-wrap items-center justify-end gap-2 rounded-b-lg border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $footer }}
                </footer>
            @endisset
        </div>
    </div>
</div>
