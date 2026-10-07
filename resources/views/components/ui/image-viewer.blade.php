{{--
    Full-screen image viewer, included once in the app layout. Any element with
    data-image-preview="<group>" and data-src / data-name / data-meta / data-download
    opens it with: x-on:click="$dispatch('preview-image', $el)". Images in the same
    group can be browsed with the arrow buttons or keys.
--}}
<div x-data="imageViewer"
    x-on:preview-image.window="show($event.detail)"
    x-on:keydown.escape.window="open && close()"
    x-on:keydown.arrow-right.window="open && next()"
    x-on:keydown.arrow-left.window="open && previous()"
    x-show="open" x-cloak x-transition.opacity
    class="fixed inset-0 z-[60] flex flex-col bg-slate-950/95 backdrop-blur-sm"
    role="dialog" aria-modal="true" aria-label="Image preview">
    <div class="flex items-center gap-3 px-4 py-3 text-white sm:px-6">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium" x-text="current?.name"></p>
            <p class="font-mono text-xs text-slate-400">
                <span x-text="current?.meta"></span>
                <span x-show="items.length > 1"> · <span x-text="index + 1"></span> of <span x-text="items.length"></span></span>
            </p>
        </div>

        <a x-show="current?.download" x-bind:href="current?.download"
            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3 text-label font-medium text-slate-900 hover:bg-slate-100">
            <x-ui.icon name="download" class="text-[18px]" />
            <span class="hidden sm:inline">Download</span>
        </a>
        <a x-show="current?.download" x-bind:href="current?.src" target="_blank" rel="noopener"
            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white" title="Open in new tab">
            <span class="sr-only">Open in new tab</span>
            <x-ui.icon name="open_in_new" class="text-[18px]" />
        </a>
        <button type="button" x-on:click="close()"
            class="inline-flex size-9 items-center justify-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white">
            <span class="sr-only">Close preview</span>
            <x-ui.icon name="close" />
        </button>
    </div>

    <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 pb-6 sm:px-16" x-on:click.self="close()">
        <img x-bind:src="current?.src" x-bind:alt="current?.name"
            class="max-h-full max-w-full rounded-lg bg-white object-contain shadow-modal">

        <template x-if="items.length > 1">
            <div>
                <button type="button" x-on:click="previous()"
                    class="absolute top-1/2 left-2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:left-4">
                    <span class="sr-only">Previous image</span>
                    <x-ui.icon name="chevron_left" />
                </button>
                <button type="button" x-on:click="next()"
                    class="absolute top-1/2 right-2 inline-flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:right-4">
                    <span class="sr-only">Next image</span>
                    <x-ui.icon name="chevron_right" />
                </button>
            </div>
        </template>
    </div>
</div>
