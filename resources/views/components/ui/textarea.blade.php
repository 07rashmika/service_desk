@props(['invalid' => null])

@php
    $name = $attributes->get('name');
    $errorKey = $name ? str_replace(['[]', '[', ']'], ['', '.', ''], $name) : null;
    $invalid ??= $errorKey && isset($errors) && $errors->has($errorKey);
@endphp

<textarea
    {{ $attributes->merge(['id' => $name, 'rows' => 4])->class([
        'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 transition placeholder:text-slate-400 focus:ring-3 focus:outline-none disabled:bg-slate-100 disabled:text-slate-500',
        'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $invalid,
        'border-slate-200 focus:border-primary-600 focus:ring-primary-600/15' => ! $invalid,
    ]) }}
    @if ($invalid) aria-invalid="true" @endif>{{ $slot }}</textarea>
