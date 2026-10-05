<nav class="flex flex-col gap-5" aria-label="Main">
    @forelse ($visibleSections() as $section)
        <div class="flex flex-col gap-0.5">
            @if ($section['heading'])
                <p class="px-3 pb-1 text-xs font-medium uppercase tracking-wider text-slate-500">{{ $section['heading'] }}</p>
            @endif

            @foreach ($section['items'] as $item)
                <x-layouts.nav-link :href="$item['href']" :icon="$item['icon']" :active="$item['active']">
                    {{ $item['label'] }}
                </x-layouts.nav-link>
            @endforeach
        </div>
    @empty
        <p class="px-3 text-xs text-slate-400">No pages yet.</p>
    @endforelse
</nav>
