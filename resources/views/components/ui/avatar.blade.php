@props(['name', 'src' => null, 'size' => 'md'])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $tones = [
        'bg-primary-100 text-primary-700',
        'bg-sky-100 text-sky-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-800',
        'bg-rose-100 text-rose-700',
        'bg-violet-100 text-violet-700',
    ];
    $tone = $tones[crc32($name) % count($tones)];

    $sizeClasses = match ($size) {
        'xs' => 'size-5 text-[9px]',
        'sm' => 'size-6 text-[10px]',
        'lg' => 'size-10 text-sm',
        default => 'size-8 text-xs',
    };
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" {{ $attributes->class(['shrink-0 rounded-full object-cover', $sizeClasses]) }}>
@else
    <span title="{{ $name }}" {{ $attributes->class(['inline-flex shrink-0 select-none items-center justify-center rounded-full font-semibold', $sizeClasses, $tone]) }}>
        {{ $initials }}
    </span>
@endif
