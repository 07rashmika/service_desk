@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class('flex flex-col items-center px-6 py-12 text-center') }}>
    <span class="mb-4 flex size-16 items-center justify-center rounded-full bg-primary-50 text-primary-600">
        <x-ui.icon :name="$icon" class="text-[32px]" />
    </span>
    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-md text-sm text-slate-500">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
