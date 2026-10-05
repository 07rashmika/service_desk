@props(['href', 'icon', 'active' => false, 'badge' => null])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
    {{ $attributes->class([
        'flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-label font-medium transition-colors',
        'bg-primary-50 text-primary-700' => $active,
        'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
    ]) }}>
    <span class="flex items-center gap-3">
        <x-ui.icon :name="$icon" @class(['icon-filled' => $active]) />
        {{ $slot }}
    </span>

    @if ($badge)
        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-xs font-medium tabular-nums text-slate-600">{{ $badge }}</span>
    @endif
</a>
