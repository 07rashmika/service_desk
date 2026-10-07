@props(['labels', 'series', 'height' => 'h-64', 'tableCaption' => 'Chart data'])

{{-- A line chart with an HTML legend and a table twin, so values never depend on hover or colour. --}}
<figure {{ $attributes->class('flex flex-col gap-3') }}>
    @if (count($series) > 1)
        <figcaption class="flex flex-wrap items-center gap-4 text-label text-slate-600">
            @foreach ($series as $line)
                <span class="flex items-center gap-2">
                    <span class="h-0.5 w-4 rounded-full" style="background-color: {{ $line['color'] }}"></span>
                    {{ $line['name'] }}
                    <span class="font-medium text-slate-900">{{ number_format(array_sum($line['data'])) }}</span>
                </span>
            @endforeach
        </figcaption>
    @endif

    <div class="relative {{ $height }}" x-data="lineChart({ labels: @js($labels), series: @js($series) })">
        <canvas x-ref="canvas" role="img" aria-label="{{ $tableCaption }}. The same numbers are in the table below."></canvas>
    </div>

    <details class="group">
        <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700">
            <x-ui.icon name="chevron_right" class="text-[16px] transition-transform group-open:rotate-90" /> Show as table
        </summary>
        <div class="mt-2 max-h-72 overflow-auto rounded-lg border border-slate-200">
            <table class="w-full text-left text-xs">
                <caption class="sr-only">{{ $tableCaption }}</caption>
                <thead class="sticky top-0 bg-slate-50 text-slate-600">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-medium">Date</th>
                        @foreach ($series as $line)
                            <th scope="col" class="px-3 py-2 text-right font-medium">{{ $line['name'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 tabular-nums">
                    @foreach ($labels as $index => $label)
                        <tr>
                            <th scope="row" class="px-3 py-1.5 font-normal text-slate-700">{{ $label }}</th>
                            @foreach ($series as $line)
                                <td class="px-3 py-1.5 text-right text-slate-900">{{ $line['data'][$index] }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
