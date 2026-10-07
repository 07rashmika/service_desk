<div class="relative"
    x-data="notificationBell({ count: @js($unreadCount), items: @js($latest), userId: @js(auth()->id()), limit: @js(\App\View\Components\Layouts\NotificationBell::LATEST) })"
    x-on:click.outside="open = false" x-on:keydown.escape="open = false">
    <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()"
        class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900">
        <span class="sr-only">Notifications</span>
        <x-ui.icon name="notifications" />
        <span x-cloak x-show="count > 0" x-text="count > 9 ? '9+' : count"
            class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-1 font-mono text-[10px] font-semibold text-white ring-2 ring-white"></span>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right
        class="absolute right-0 z-50 mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-lg border border-slate-200 bg-white shadow-popover">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <p class="font-semibold text-slate-900">Notifications</p>
            <form method="POST" action="{{ route('notifications.read-all') }}" x-show="count > 0">
                @csrf
                <button type="submit" class="text-label font-medium text-primary-600 hover:text-primary-700">Mark all as read</button>
            </form>
        </div>

        <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            <template x-for="item in items" :key="item.id">
                <li>
                    <a x-bind:href="item.url" class="flex gap-3 px-4 py-3 hover:bg-slate-50" x-bind:class="! item.read && 'bg-primary-50/40'">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full" x-bind:class="colorClasses(item.color)">
                            <span class="material-icon text-[18px]" x-text="item.icon"></span>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-label text-slate-900" x-text="item.message"></span>
                            <span class="block text-xs text-slate-500" x-text="item.time"></span>
                        </span>
                        <span x-show="! item.read" class="mt-1.5 size-2 shrink-0 rounded-full bg-primary-600" aria-label="Unread"></span>
                    </a>
                </li>
            </template>
        </ul>
        <p x-show="items.length === 0" class="px-4 py-8 text-center text-label text-slate-500">You're all caught up.</p>

        <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2.5 text-center text-label font-medium text-primary-600 hover:bg-slate-50">
            View all notifications
        </a>
    </div>
</div>
