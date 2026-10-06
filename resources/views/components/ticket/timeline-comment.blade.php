@props(['author', 'role' => null, 'time' => null, 'meta' => null, 'internal' => false, 'avatar' => null])

<article {{ $attributes->class([
    'rounded-lg border p-5',
    'border-primary-200 bg-primary-50/60' => $internal,
    'border-slate-200 bg-white' => ! $internal,
]) }}>
    @if ($internal)
        <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-primary-700">
            <x-ui.icon name="lock" class="text-[16px]" />
            Internal note – not visible to employee
        </p>
    @endif

    <header class="flex items-start gap-3">
        <x-ui.avatar :name="$author" :src="$avatar" />
        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-semibold text-slate-900">{{ $author }}</span>
                @if ($role)
                    <x-ui.badge color="primary">{{ $role }}</x-ui.badge>
                @endif
            </p>
            @if ($time || $meta)
                <p class="text-xs text-slate-500">{{ collect([$time, $meta])->filter()->implode(' · ') }}</p>
            @endif
        </div>
        {{ $actions ?? '' }}
    </header>

    {{-- The slot is already escaped by Blade; trimming stops indentation showing up as blank lines. --}}
    <div class="mt-3 text-sm leading-6 break-words whitespace-pre-line text-slate-700">{!! trim($slot) !!}</div>

    @isset($attachments)
        <div class="mt-4 flex flex-wrap gap-2">{{ $attachments }}</div>
    @endisset
</article>
