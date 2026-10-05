@props([
    'name',
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
    'show' => false,
])

@php
    $widthClass = match ($maxWidth) {
        'md' => 'max-w-md',
        'xl' => 'max-w-xl',
        default => 'max-w-lg',
    };
@endphp

{{-- Open with $dispatch('open-modal', '{{ $name }}'). Footer buttons can submit a form in the body via form="form-id". --}}
<div x-data="{ show: @js($show), name: @js($name) }"
    x-on:open-modal.window="if ($event.detail === name) show = true"
    x-on:close-modal.window="if ($event.detail === name) show = false"
    x-on:keydown.escape.window="show = false"
    x-effect="document.body.classList.toggle('overflow-hidden', show)"
    x-show="show" x-cloak
    class="fixed inset-0 z-50" role="dialog" aria-modal="true"
    @if ($title) aria-labelledby="{{ $name }}-title" @endif>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-slate-900/50" x-on:click="show = false"></div>

    <div x-show="show"
        x-transition:enter="transform transition duration-200 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition duration-150 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        {{ $attributes->class(['fixed inset-y-0 right-0 flex w-full flex-col bg-white shadow-modal', $widthClass]) }}>
        @if ($title)
            <header class="flex items-start gap-3 border-b border-slate-200 px-6 py-4">
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

        <div class="flex-1 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>

        @isset($footer)
            <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-4">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</div>
