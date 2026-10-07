<x-layouts.app title="Priorities & SLA">
    <x-ui.page-header eyebrow="Service configuration" title="Priorities & SLA" description="How urgent a ticket is, and how quickly IT must respond and resolve it.">
        <x-slot:actions>
            <x-ui.button icon="add" x-data x-on:click="$dispatch('open-modal', 'create-priority')">Add priority</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
    <x-admin.settings-tabs />

    <x-ui.table>
        <x-slot:head>
            <x-ui.table.heading>Priority</x-ui.table.heading>
            <x-ui.table.heading>Level</x-ui.table.heading>
            <x-ui.table.heading>First response</x-ui.table.heading>
            <x-ui.table.heading>Resolution</x-ui.table.heading>
            <x-ui.table.heading class="hidden md:table-cell">Tickets</x-ui.table.heading>
            <x-ui.table.heading class="w-24"><span class="sr-only">Actions</span></x-ui.table.heading>
        </x-slot:head>
        @foreach ($priorities as $priority)
            <tr class="transition-colors hover:bg-slate-50">
                <x-ui.table.cell>
                    <x-ticket.priority-badge :priority="$priority" />
                    <p class="mt-1 text-xs text-slate-500">{{ $priority->description }}</p>
                </x-ui.table.cell>
                <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-600">{{ $priority->level }}</x-ui.table.cell>
                <x-ui.table.cell class="whitespace-nowrap text-slate-700">{{ $priority->response_hours }} {{ str('hour')->plural($priority->response_hours) }}</x-ui.table.cell>
                <x-ui.table.cell class="whitespace-nowrap text-slate-700">{{ $priority->resolution_hours }} {{ str('hour')->plural($priority->resolution_hours) }}</x-ui.table.cell>
                <x-ui.table.cell class="hidden font-mono text-xs tabular-nums text-slate-600 md:table-cell">{{ $priority->tickets_count }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('admin.priorities.index', ['edit' => $priority->id]) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit">
                            <span class="sr-only">Edit {{ $priority->name }}</span>
                            <x-ui.icon name="edit" class="text-[18px]" />
                        </a>
                        <form method="POST" action="{{ route('admin.priorities.destroy', $priority) }}"
                            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete the '.$priority->name.' priority?') }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-40" @disabled($priority->tickets_count > 0)
                                title="{{ $priority->tickets_count > 0 ? 'Used by tickets' : 'Delete' }}">
                                <span class="sr-only">Delete {{ $priority->name }}</span>
                                <x-ui.icon name="delete" class="text-[18px]" />
                            </button>
                        </form>
                    </div>
                </x-ui.table.cell>
            </tr>
        @endforeach
    </x-ui.table>

    <x-ui.modal name="create-priority" title="Add priority" icon="timer" max-width="xl" :show="$errors->createPriority->isNotEmpty()">
        <form id="create-priority-form" method="POST" action="{{ route('admin.priorities.store') }}">
            @csrf
            @include('admin.priorities.form-fields', ['priority' => null, 'bag' => 'createPriority'])
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'create-priority')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="create-priority-form">Add priority</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    @if ($editing)
        <x-ui.modal name="edit-priority" :title="'Edit priority: '.$editing->name" icon="timer" max-width="xl" show>
            <form id="edit-priority-form" method="POST" action="{{ route('admin.priorities.update', $editing) }}">
                @csrf
                @method('PUT')
                @include('admin.priorities.form-fields', ['priority' => $editing, 'bag' => 'editPriority'])
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.priorities.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="edit-priority-form">Save priority</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</x-layouts.app>
