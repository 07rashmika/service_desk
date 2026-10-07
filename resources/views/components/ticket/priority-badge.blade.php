@props(['priority'])

{{-- The most urgent level gets a pulsing dot so it stands out in lists. --}}
<x-ui.badge :color="$priority->color" :dot="$priority->level === 3" :pulse="$priority->level >= 4" {{ $attributes }}>
    {{ $priority->name }}
</x-ui.badge>
