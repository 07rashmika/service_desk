@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="flex min-h-full flex-col bg-gradient-to-b from-primary-50/60 to-slate-50">
    <header class="flex h-16 items-center px-4 sm:px-6">
        <a href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
            <x-ui.logo />
        </a>
    </header>

    <main class="flex flex-1 items-start justify-center px-4 pt-6 pb-12 sm:items-center sm:pt-0">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </main>

    <footer class="px-4 pb-6 text-center text-xs text-slate-500">
        &copy; {{ date('Y') }} {{ config('app.name') }} · IT support for company employees
    </footer>
</body>
</html>
