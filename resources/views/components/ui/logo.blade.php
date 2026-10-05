@props(['showText' => true])

<span {{ $attributes->class('inline-flex items-center gap-2') }}>
    <svg class="size-8 shrink-0" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect width="32" height="32" rx="8" class="fill-primary-600" />
        <path d="M21.66 10.34A8 8 0 1 0 24 16" stroke="white" stroke-width="2.5" stroke-linecap="round" />
        <circle cx="16" cy="16" r="3" fill="white" />
    </svg>
    @if ($showText)
        <span class="text-base font-semibold tracking-tight text-slate-900">Service<span class="text-primary-600">Desk</span></span>
    @endif
</span>
