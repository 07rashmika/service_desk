@props(['status'])

<x-ui.badge :color="$status->color" {{ $attributes }}>{{ $status->name }}</x-ui.badge>
