@props(['code', 'icon', 'title', 'description', 'minimal' => false])

{{--
    Server errors use the minimal guest layout so the error page itself can't fail on a
    database or permission lookup; other errors keep the app shell for signed-in users.
--}}
<x-dynamic-component :component="! $minimal && auth()->check() ? 'layouts.app' : 'layouts.guest'" :title="$title">
    <div class="flex flex-1 flex-col items-center justify-center px-4 py-16 text-center">
        <span class="mb-5 flex size-16 items-center justify-center rounded-full bg-primary-50 text-primary-600">
            <x-ui.icon :name="$icon" class="text-[32px]" />
        </span>
        <p class="font-mono text-xs font-medium tracking-wider text-primary-600 uppercase">Error {{ $code }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ $title }}</h1>
        <p class="mt-2 max-w-md text-sm text-slate-500">{{ $description }}</p>

        <div class="mt-8 flex flex-wrap justify-center gap-2">
            @if (! $minimal && auth()->check())
                <x-ui.button :href="route('dashboard')" icon="home">Go to dashboard</x-ui.button>
            @else
                <x-ui.button :href="url('/')" icon="home">Go to ServiceDesk</x-ui.button>
            @endif
        </div>
    </div>
</x-dynamic-component>
