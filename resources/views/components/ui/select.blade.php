@props(['invalid' => null])

@php
    $name = $attributes->get('name');
    $errorKey = $name ? str_replace(['[]', '[', ']'], ['', '.', ''], $name) : null;
    $invalid ??= $errorKey && isset($errors) && $errors->has($errorKey);
@endphp

<div class="relative">
    <select
        {{ $attributes->merge(['id' => $name])->class([
            'block h-[38px] w-full appearance-none rounded-lg border bg-white pr-9 pl-3 text-sm text-slate-900 transition focus:ring-3 focus:outline-none disabled:bg-slate-100 disabled:text-slate-500',
            'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $invalid,
            'border-slate-200 focus:border-primary-600 focus:ring-primary-600/15' => ! $invalid,
        ]) }}
        @if ($invalid) aria-invalid="true" @endif>
        {{ $slot }}
    </select>
    <x-ui.icon name="unfold_more" class="pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-[18px] text-slate-400" />
</div>
