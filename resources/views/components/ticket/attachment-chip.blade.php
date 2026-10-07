@props(['attachment'])

@php
    $downloadUrl = route('attachments.show', $attachment);
    $size = \Illuminate\Support\Number::fileSize($attachment->size, maxPrecision: 1);
    $chipClasses = 'group flex max-w-full min-w-0 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 pr-2 transition-colors hover:border-primary-200 hover:bg-white';
@endphp

@if ($attachment->isImage())
    {{-- Images open in the full-screen viewer; the icon on the right still downloads them. --}}
    @php($previewUrl = route('attachments.show', [$attachment, 'inline' => 1]))
    <div {{ $attributes->class($chipClasses) }}>
        <button type="button" class="flex min-w-0 items-center gap-3 rounded text-left"
            title="Preview {{ $attachment->original_name }}"
            data-image-preview="ticket-{{ $attachment->ticket_id }}"
            data-src="{{ $previewUrl }}"
            data-name="{{ $attachment->original_name }}"
            data-meta="{{ $size }}"
            data-download="{{ $downloadUrl }}"
            x-on:click="$dispatch('preview-image', $el)">
            <span class="relative shrink-0">
                <img src="{{ $previewUrl }}" alt="" loading="lazy" class="size-10 rounded border border-slate-200 bg-white object-cover">
                <span class="absolute inset-0 flex items-center justify-center rounded bg-slate-900/50 text-white opacity-0 transition-opacity group-hover:opacity-100">
                    <x-ui.icon name="zoom_in" class="text-[18px]" />
                </span>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-label font-medium text-slate-900">{{ $attachment->original_name }}</span>
                <span class="block font-mono text-xs text-slate-500">{{ $size }} · Click to preview</span>
            </span>
        </button>
        <a href="{{ $downloadUrl }}" title="Download {{ $attachment->original_name }}"
            class="ml-1 inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-primary-600">
            <span class="sr-only">Download {{ $attachment->original_name }}</span>
            <x-ui.icon name="download" class="text-[18px]" />
        </a>
    </div>
@else
    <a href="{{ $downloadUrl }}" title="Download {{ $attachment->original_name }}" {{ $attributes->class([$chipClasses, 'pr-3']) }}>
        <span class="flex size-10 shrink-0 items-center justify-center rounded bg-white text-slate-500 ring-1 ring-slate-200">
            <x-ui.icon :name="$attachment->mime_type === 'application/pdf' ? 'picture_as_pdf' : 'description'" />
        </span>
        <span class="min-w-0">
            <span class="block truncate text-label font-medium text-slate-900">{{ $attachment->original_name }}</span>
            <span class="block font-mono text-xs text-slate-500">{{ $size }}</span>
        </span>
        <x-ui.icon name="download" class="ml-1 text-[18px] text-slate-400 group-hover:text-primary-600" />
    </a>
@endif
