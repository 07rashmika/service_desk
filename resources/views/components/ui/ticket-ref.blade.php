@props(['href' => null])

@php
    $classes = 'inline-flex items-center whitespace-nowrap rounded border border-slate-200 bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-medium tabular-nums text-slate-600';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes, 'hover:border-primary-200 hover:text-primary-700']) }}>{{ $slot }}</a>
@else
    <span {{ $attributes->class($classes) }}>{{ $slot }}</span>
@endif
