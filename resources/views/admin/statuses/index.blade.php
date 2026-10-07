<x-layouts.app title="Statuses">
    <x-ui.page-header eyebrow="Service configuration" title="Statuses"
        description="The six steps every ticket goes through. You can rename them and change their colours; the steps themselves are fixed because the workflow depends on them." />
    <x-admin.settings-tabs />

    <x-ui.table>
        <x-slot:head>
            <x-ui.table.heading class="w-16">Step</x-ui.table.heading>
            <x-ui.table.heading>Status</x-ui.table.heading>
            <x-ui.table.heading class="hidden md:table-cell">System name</x-ui.table.heading>
            <x-ui.table.heading>Tickets</x-ui.table.heading>
            <x-ui.table.heading class="w-16"><span class="sr-only">Edit</span></x-ui.table.heading>
        </x-slot:head>
        @foreach ($statuses as $status)
            <tr class="transition-colors hover:bg-slate-50">
                <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-500">{{ $status->sort_order }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <x-ticket.status-badge :status="$status" />
                    @if ($status->is_final)
                        <span class="ml-2 text-xs text-slate-500">Final step</span>
                    @endif
                </x-ui.table.cell>
                <x-ui.table.cell class="hidden font-mono text-xs text-slate-500 md:table-cell">{{ $status->slug->value }}</x-ui.table.cell>
                <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-600">{{ $status->tickets_count }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <a href="{{ route('admin.statuses.index', ['edit' => $status->id]) }}" class="inline-flex rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit">
                        <span class="sr-only">Edit {{ $status->name }}</span>
                        <x-ui.icon name="edit" class="text-[18px]" />
                    </a>
                </x-ui.table.cell>
            </tr>
        @endforeach
    </x-ui.table>

    @if ($editing)
        <x-ui.modal name="edit-status" :title="'Edit status: '.$editing->name" icon="rule" show
            :description="'System name: '.$editing->slug->value.' (fixed)'">
            <form id="edit-status-form" method="POST" action="{{ route('admin.statuses.update', $editing) }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')
                <x-ui.field label="Name" for="status-name" required :error="$errors->editStatus->first('name')">
                    <x-ui.input id="status-name" name="name" :value="old('name', $editing->name)" required maxlength="50" :invalid="$errors->editStatus->has('name')" />
                </x-ui.field>
                <x-ui.field label="Colour" required :error="$errors->editStatus->first('color')">
                    <x-ui.color-picker name="color" :selected="old('color', $editing->color->value)" />
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.statuses.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="edit-status-form">Save status</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</x-layouts.app>
