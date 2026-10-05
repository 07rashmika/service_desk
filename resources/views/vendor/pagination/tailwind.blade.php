@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-label text-slate-600">
            @if ($paginator->firstItem())
                Showing <span class="font-medium text-slate-900">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                of <span class="font-medium text-slate-900">{{ $paginator->total() }}</span>
            @else
                Showing {{ $paginator->count() }} of {{ $paginator->total() }}
            @endif
        </p>

        @if ($paginator->hasPages())
            @php
                $linkClasses = 'inline-flex h-8 min-w-8 items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-label font-medium text-slate-700 transition-colors hover:bg-slate-50';
                $disabledClasses = 'inline-flex h-8 items-center gap-1 rounded-lg border border-slate-100 px-2.5 text-label font-medium text-slate-300';
            @endphp

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabledClasses }}" aria-disabled="true">
                        <x-ui.icon name="chevron_left" class="text-[18px]" /> Previous
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $linkClasses }}">
                        <x-ui.icon name="chevron_left" class="text-[18px]" /> Previous
                    </a>
                @endif

                <div class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-1 text-slate-400" aria-disabled="true">{{ $element }}</span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-primary-600 px-2.5 text-label font-medium text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="{{ $linkClasses }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $linkClasses }}">
                        Next <x-ui.icon name="chevron_right" class="text-[18px]" />
                    </a>
                @else
                    <span class="{{ $disabledClasses }}" aria-disabled="true">
                        Next <x-ui.icon name="chevron_right" class="text-[18px]" />
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
