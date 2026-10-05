@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="min-h-full" x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">
    {{-- Mobile backdrop --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden"></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
        x-bind:class="{ 'translate-x-0': sidebarOpen }">
        <div class="flex h-[60px] shrink-0 items-center justify-between border-b border-slate-200 px-5">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }} home">
                <x-ui.logo />
            </a>
            <button type="button" class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 lg:hidden" x-on:click="sidebarOpen = false">
                <span class="sr-only">Close menu</span>
                <x-ui.icon name="close" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-4">
            <x-layouts.navigation />
        </div>

        @auth
            <div class="shrink-0 border-t border-slate-200 p-2">
                <div class="flex items-center gap-3 rounded-lg p-2">
                    <x-ui.avatar :name="auth()->user()->name" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-label font-medium text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->primaryRole()?->label() ?? auth()->user()->job_title }}</p>
                    </div>
                </div>
            </div>
        @endauth
    </aside>

    <div class="flex min-h-full flex-col lg:pl-60">
        <header class="sticky top-0 z-30 flex h-[60px] shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <button type="button" class="-ml-1 rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden" x-on:click="sidebarOpen = true">
                <span class="sr-only">Open menu</span>
                <x-ui.icon name="menu" />
            </button>

            <form role="search" method="GET" class="relative w-full max-w-md"
                action="{{ Route::has('tickets.index') ? route('tickets.index') : url()->current() }}">
                <label for="global-search" class="sr-only">Search tickets</label>
                <x-ui.icon name="search" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-[18px] text-slate-400" />
                <input id="global-search" name="search" type="search" value="{{ request('search') }}" placeholder="Search tickets…"
                    class="h-9 w-full rounded-lg border border-slate-200 bg-white pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-primary-600 focus:ring-3 focus:ring-primary-600/15 focus:outline-none">
            </form>

            <div class="ml-auto flex items-center gap-1">
                {{ $headerActions ?? '' }}

                @if (Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                        <span class="sr-only">Notifications</span>
                        <x-ui.icon name="notifications" />
                    </a>
                @endif

                @auth
                    <x-ui.dropdown align="right">
                        <x-slot:trigger>
                            <button type="button" class="flex items-center gap-1 rounded-lg p-1 hover:bg-slate-100">
                                <x-ui.avatar :name="auth()->user()->name" />
                                <x-ui.icon name="expand_more" class="text-[18px] text-slate-400" />
                            </button>
                        </x-slot:trigger>

                        <div class="border-b border-slate-100 px-3 py-2">
                            <p class="truncate text-label font-medium text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        @if (Route::has('profile.edit'))
                            <x-ui.dropdown-link :href="route('profile.edit')" icon="account_circle">Profile</x-ui.dropdown-link>
                        @endif
                        @if (Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-ui.dropdown-link as="button" type="submit" icon="logout">Sign out</x-ui.dropdown-link>
                            </form>
                        @endif
                    </x-ui.dropdown>
                @endauth
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:py-8">
            @if (session('success'))
                <x-ui.alert type="success" dismissible>{{ session('success') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert type="error" dismissible>{{ session('error') }}</x-ui.alert>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
