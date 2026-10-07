@props(['attachment'])

<a href="{{ route('attachments.show', $attachment) }}" title="Download {{ $attachment->original_name }}"
    {{ $attributes->class('group flex max-w-full min-w-0 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-2 pr-3 transition-colors hover:border-primary-200 hover:bg-white') }}>
    @if ($attachment->isImage())
        <img src="{{ route('attachments.show', [$attachment, 'inline' => 1]) }}" alt="" loading="lazy"
            class="size-10 shrink-0 rounded border border-slate-200 bg-white object-cover">
    @else
        <span class="flex size-10 shrink-0 items-center justify-center rounded bg-white text-slate-500 ring-1 ring-slate-200">
            <x-ui.icon :name="$attachment->mime_type === 'application/pdf' ? 'picture_as_pdf' : 'description'" />
        </span>
    @endif
    <span class="min-w-0">
        <span class="block truncate text-label font-medium text-slate-900">{{ $attachment->original_name }}</span>
        <span class="block font-mono text-xs text-slate-500">{{ \Illuminate\Support\Number::fileSize($attachment->size, maxPrecision: 1) }}</span>
    </span>
    <x-ui.icon name="download" class="ml-1 text-[18px] text-slate-400 group-hover:text-primary-600" />
</a>
