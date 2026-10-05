<div {{ $attributes->class('overflow-hidden rounded-lg border border-slate-200 bg-white') }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            @isset($head)
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-slate-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
