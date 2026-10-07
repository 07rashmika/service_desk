@props(['volume', 'days'])

<x-ui.card {{ $attributes }} title="Tickets created vs resolved" :description="'Per day, last '.$days.' days'">
    <x-chart.line :labels="$volume['labels']" table-caption="Tickets created and resolved per day" :series="[
        ['name' => 'Created', 'color' => '#4f46e5', 'data' => $volume['created']],
        ['name' => 'Resolved', 'color' => '#eb6834', 'data' => $volume['resolved']],
    ]" />
</x-ui.card>
