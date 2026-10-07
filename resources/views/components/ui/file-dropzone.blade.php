@props([
    'name' => 'attachments[]',
    'accept' => '.png,.jpg,.jpeg,.gif,.webp,.pdf,.txt,.log',
    'maxFiles' => 5,
    'maxSizeMb' => 5,
    'hint' => null,
    'compact' => false,
])

@php
    $id = $attributes->get('id', str_replace(['[]', '[', ']'], ['', '-', ''], $name));
@endphp

<div x-data="fileDropzone({ maxFiles: @js($maxFiles), maxSizeMb: @js($maxSizeMb) })" {{ $attributes->except('id')->class('flex flex-col gap-3') }}>
    @if ($compact)
        <label for="{{ $id }}"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; add($event.dataTransfer.files)"
            x-bind:class="dragging ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            class="inline-flex w-fit cursor-pointer items-center gap-1.5 rounded-lg border border-dashed px-2.5 py-1.5 text-label font-medium transition-colors">
            <x-ui.icon name="attach_file" class="text-[18px]" />
            Attach files
            <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept }}" multiple class="sr-only"
                x-ref="input" x-on:change="add($event.target.files)">
        </label>
    @else
        <label for="{{ $id }}"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; add($event.dataTransfer.files)"
            x-bind:class="dragging ? 'border-primary-500 bg-primary-50' : 'border-slate-300 bg-white hover:bg-slate-50'"
            class="flex cursor-pointer flex-col items-center gap-1 rounded-lg border-2 border-dashed px-6 py-8 text-center transition-colors">
            <span class="mb-2 flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                <x-ui.icon name="cloud_upload" />
            </span>
            <span class="text-sm font-medium text-slate-900">Drop screenshots or files here</span>
            <span class="text-xs text-slate-500">{{ $hint ?? "Up to {$maxFiles} files, max {$maxSizeMb} MB each" }}</span>
            <span class="mt-1 text-label font-medium text-primary-600">or browse files</span>
            <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept }}" multiple class="sr-only"
                x-ref="input" x-on:change="add($event.target.files)">
        </label>
    @endif

    <p x-cloak x-show="error" x-text="error" class="text-xs text-red-600"></p>

    <ul x-cloak x-show="files.length" class="flex flex-col gap-2">
        <template x-for="(file, index) in files" :key="file.name + file.size + file.lastModified">
            <li class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-2.5">
                <template x-if="isImage(file)">
                    <button type="button" class="group relative shrink-0" title="Preview"
                        data-image-preview="upload-{{ $id }}"
                        x-bind:data-src="previewUrl(file)"
                        x-bind:data-name="file.name"
                        x-bind:data-meta="formatSize(file.size) + ' · not uploaded yet'"
                        x-on:click="$dispatch('preview-image', $el)">
                        <img x-bind:src="previewUrl(file)" alt="" class="size-9 rounded border border-slate-200 object-cover">
                        <span class="absolute inset-0 flex items-center justify-center rounded bg-slate-900/50 text-white opacity-0 transition-opacity group-hover:opacity-100">
                            <x-ui.icon name="zoom_in" class="text-[16px]" />
                        </span>
                    </button>
                </template>
                <template x-if="! isImage(file)">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded bg-slate-100 text-slate-500">
                        <x-ui.icon name="description" />
                    </span>
                </template>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-label font-medium text-slate-900" x-text="file.name"></span>
                    <span class="block font-mono text-xs text-slate-500" x-text="formatSize(file.size)"></span>
                </span>
                <button type="button" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600" x-on:click="remove(index)">
                    <span class="sr-only">Remove file</span>
                    <x-ui.icon name="close" class="text-[18px]" />
                </button>
            </li>
        </template>
    </ul>
</div>
