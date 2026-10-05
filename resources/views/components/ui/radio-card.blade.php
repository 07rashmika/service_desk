@props(['label', 'description' => null, 'color' => null])

<label class="group relative flex cursor-pointer flex-col gap-1 rounded-lg border border-slate-200 bg-white p-4 transition-colors hover:border-slate-300 has-checked:border-primary-600 has-checked:bg-primary-50/40 has-checked:ring-1 has-checked:ring-primary-600 has-focus-visible:ring-2 has-focus-visible:ring-primary-600">
    <input type="radio" {{ $attributes->class('sr-only') }}>
    <span class="flex items-center justify-between gap-2">
        <span class="flex items-center gap-2 text-sm font-medium text-slate-900">
            @if ($color)
                <span @class(['size-2 rounded-full', \App\Enums\Palette::fromName($color)->dotClass()])></span>
            @endif
            {{ $label }}
        </span>
        <x-ui.icon name="radio_button_unchecked" class="text-[18px] text-slate-300 group-has-checked:hidden" />
        <x-ui.icon name="check_circle" class="icon-filled hidden text-[18px] text-primary-600 group-has-checked:inline" />
    </span>
    @if ($description)
        <span class="text-label text-slate-500">{{ $description }}</span>
    @endif
</label>
