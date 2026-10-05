@props(['name', 'filled' => false])

<span {{ $attributes->class(['material-icon shrink-0', 'icon-filled' => $filled]) }} aria-hidden="true">{{ $name }}</span>
