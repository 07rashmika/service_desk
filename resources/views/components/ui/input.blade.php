@props(['icon' => null, 'invalid' => null, 'errorIcon' => true])

@php
    $name = $attributes->get('name');
    $errorKey = $name ? str_replace(['[]', '[', ']'], ['', '.', ''], $name) : null;
    $invalid ??= $errorKey && isset($errors) && $errors->has($errorKey);
@endphp

<div class="relative">
    @if ($icon)
        <x-ui.icon :name="$icon" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[18px] text-slate-400" />
    @endif

    <input
        {{ $attributes->merge(['type' => 'text', 'id' => $name])->class([
            'block h-[38px] w-full rounded-lg border bg-white px-3 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-3 focus:outline-none disabled:bg-slate-100 disabled:text-slate-500',
            'pl-9' => $icon,
            'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $invalid,
            'pr-9' => $invalid && $errorIcon,
            'border-slate-200 focus:border-primary-600 focus:ring-primary-600/15' => ! $invalid,
        ]) }}
        @if ($invalid) aria-invalid="true" @endif>

    @if ($invalid && $errorIcon)
        <x-ui.icon name="error" class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-[18px] text-red-500" />
    @endif
</div>
