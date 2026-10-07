@props(['name' => 'color', 'selected' => null])

@php
    $selectedPalette = $selected ? \App\Enums\Palette::fromName($selected) : null;
@endphp

{{-- Swatches for the named badge palettes (App\Enums\Palette). --}}
<div {{ $attributes->class('flex flex-wrap gap-2') }} role="radiogroup">
    @foreach (\App\Enums\Palette::cases() as $palette)
        <label class="group relative cursor-pointer" title="{{ $palette->label() }}">
            <input type="radio" name="{{ $name }}" value="{{ $palette->value }}" class="peer sr-only" @checked($selectedPalette === $palette)>
            <span @class(['flex size-8 items-center justify-center rounded-full ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-slate-900 peer-focus-visible:ring-2 peer-focus-visible:ring-primary-600', $palette->dotClass()])>
                <x-ui.icon name="check" class="hidden text-[16px] text-white group-has-checked:inline" />
            </span>
            <span class="sr-only">{{ $palette->label() }}</span>
        </label>
    @endforeach
</div>
