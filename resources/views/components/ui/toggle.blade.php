@props(['label' => null, 'description' => null])

<label class="flex cursor-pointer items-start justify-between gap-4">
    @if ($label || $description)
        <span class="flex flex-col">
            <span class="text-label font-medium text-slate-900">{{ $label }}</span>
            @if ($description)
                <span class="text-xs text-slate-500">{{ $description }}</span>
            @endif
        </span>
    @endif
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" role="switch" {{ $attributes->class('peer sr-only') }}>
        <span class="h-5 w-9 rounded-full bg-slate-200 transition-colors peer-checked:bg-primary-600 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-600 peer-focus-visible:ring-offset-2 peer-disabled:opacity-50"></span>
        <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-4"></span>
    </span>
</label>
