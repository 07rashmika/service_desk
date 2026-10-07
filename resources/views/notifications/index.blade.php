@php
    $chipClasses = [
        'slate' => 'bg-slate-100 text-slate-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'orange' => 'bg-orange-50 text-orange-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'red' => 'bg-red-50 text-red-600',
        'primary' => 'bg-primary-50 text-primary-600',
    ];
@endphp

<x-layouts.app title="Notifications">
    <x-ui.page-header title="Notifications" description="Updates on tickets you reported or are working on. New ones also appear live in the bell.">
        @if ($unreadCount > 0)
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary" icon="done_all">Mark all as read</x-ui.button>
                </form>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.tabs :items="[
        ['label' => 'All', 'href' => route('notifications.index'), 'count' => $totalCount, 'active' => ! $showUnread],
        ['label' => 'Unread', 'href' => route('notifications.index', ['tab' => 'unread']), 'count' => $unreadCount, 'active' => $showUnread, 'alert' => $unreadCount > 0],
    ]" />

    @if ($notifications->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state icon="notifications_off" :title="$showUnread ? 'No unread notifications' : 'No notifications yet'"
                description="You'll be notified here when a ticket you're involved in is assigned, replied to or resolved." />
        </x-ui.card>
    @else
        @foreach ($groups as $label => $items)
            <section class="flex flex-col gap-2">
                <h2 class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ $label }}</h2>
                <ul class="divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    @foreach ($items as $notification)
                        @php($item = $present($notification))
                        <li>
                            <a href="{{ $item['url'] }}" @class(['flex items-start gap-3 px-4 py-3.5 hover:bg-slate-50', 'bg-primary-50/40' => ! $item['read']])>
                                <span @class(['flex size-9 shrink-0 items-center justify-center rounded-full', $chipClasses[$item['color']] ?? $chipClasses['slate']])>
                                    <x-ui.icon :name="$item['icon']" class="text-[18px]" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span @class(['block text-sm', 'font-medium text-slate-900' => ! $item['read'], 'text-slate-700' => $item['read']])>{{ $item['message'] }}</span>
                                    <span class="block text-xs text-slate-500" title="{{ $notification->created_at->toDayDateTimeString() }}">{{ $item['time'] }}</span>
                                </span>
                                @unless ($item['read'])
                                    <span class="mt-2 size-2 shrink-0 rounded-full bg-primary-600"><span class="sr-only">Unread</span></span>
                                @endunless
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        {{ $notifications->links() }}
    @endif
</x-layouts.app>
