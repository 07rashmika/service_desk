@props(['label' => null, 'description' => null])

<label class="flex cursor-pointer items-start gap-3">
    <input type="checkbox" {{ $attributes->class('mt-0.5 size-4 shrink-0 cursor-pointer rounded border-slate-300 accent-primary-600') }}>
    <span class="flex flex-col">
        <span class="text-label font-medium text-slate-900">{{ $label ?? $slot }}</span>
        @if ($description)
            <span class="text-xs text-slate-500">{{ $description }}</span>
        @endif
    </span>
</label>
