@props(['title' => null, 'description' => null, 'padding' => true])

<section {{ $attributes->class('rounded-lg border border-slate-200 bg-white') }}>
    @if ($title || isset($actions))
        <header @class(['flex flex-wrap items-start justify-between gap-3 px-5 pt-4', 'border-b border-slate-200 pb-4' => ! $padding])>
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-semibold text-slate-900">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-label text-slate-500">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5' => $padding, 'pt-4' => $padding && $title])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 text-label text-slate-600">
            {{ $footer }}
        </footer>
    @endisset
</section>
