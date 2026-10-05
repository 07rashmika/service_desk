@props(['title', 'description' => null, 'eyebrow' => null])

<div {{ $attributes->class('flex flex-wrap items-end justify-between gap-4') }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="mb-1 text-xs font-medium uppercase tracking-wider text-primary-600">{{ $eyebrow }}</p>
        @endif
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $title }}</h1>
            {{ $meta ?? '' }}
        </div>
        @if ($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
