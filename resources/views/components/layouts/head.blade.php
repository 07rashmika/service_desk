@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'ServiceDesk') }}</title>

<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

@fonts
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..600,0..1,0&display=block">

@vite(['resources/css/app.css', 'resources/js/app.js'])
