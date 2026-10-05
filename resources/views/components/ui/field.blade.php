@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false, 'corner' => null])

@php
    $errorKey = $for ? str_replace(['[]', '[', ']'], ['', '.', ''], $for) : null;
    $error ??= ($errorKey && isset($errors)) ? $errors->first($errorKey) : null;
@endphp

<div {{ $attributes->class('flex flex-col gap-1.5') }}>
    @if ($label || $corner)
        <div class="flex items-baseline justify-between gap-3">
            @if ($label)
                <label @if ($for) for="{{ $for }}" @endif class="text-label font-medium text-slate-900">
                    {{ $label }}
                    @if ($required)
                        <span class="text-red-600" aria-hidden="true">*</span>
                    @endif
                </label>
            @endif
            @if ($corner)
                <span class="text-xs text-slate-500">{{ $corner }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if ($error)
        <p class="flex items-center gap-1 text-xs text-red-600">
            <x-ui.icon name="error" class="icon-filled text-[14px]" />
            {{ $error }}
        </p>
    @elseif ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
