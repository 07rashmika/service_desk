@props(['items' => []])

{{-- Each item: ['label' => string, 'href' => string, 'count' => ?int, 'active' => bool, 'alert' => bool] --}}
<nav {{ $attributes->class('-mx-1 overflow-x-auto px-1') }} aria-label="Tabs">
    <div class="inline-flex min-w-max items-center gap-1 rounded-lg bg-slate-100 p-1">
        @foreach ($items as $item)
            @php($active = $item['active'] ?? false)
            <a href="{{ $item['href'] }}" @if ($active) aria-current="page" @endif
                @class([
                    'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-label font-medium whitespace-nowrap transition-colors',
                    'bg-white text-primary-700 shadow-sm' => $active,
                    'text-slate-600 hover:text-slate-900' => ! $active,
                ])>
                @if ($item['alert'] ?? false)
                    <span class="size-1.5 rounded-full bg-red-500"></span>
                @endif
                {{ $item['label'] }}
                @isset($item['count'])
                    <span @class([
                        'rounded-full px-1.5 text-xs tabular-nums',
                        'bg-primary-50 text-primary-700' => $active,
                        'bg-slate-200/70 text-slate-600' => ! $active,
                    ])>{{ $item['count'] }}</span>
                @endisset
            </a>
        @endforeach
    </div>
</nav>
