<x-layouts.app title="Departments">
    <x-ui.page-header eyebrow="Service configuration" title="Departments" description="The teams people belong to. Shown on tickets and used in reports." />
    <x-admin.settings-tabs />

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <x-ui.card title="Add a department" class="lg:order-last">
            <form method="POST" action="{{ route('admin.departments.store') }}" class="flex flex-col gap-4">
                @csrf
                <x-ui.field label="Name" for="name" required :error="$errors->createDepartment->first('name')">
                    <x-ui.input name="name" :value="old('name')" required maxlength="100" placeholder="e.g. Legal" :invalid="$errors->createDepartment->has('name')" />
                </x-ui.field>
                <x-ui.button type="submit" icon="add">Add department</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.table class="lg:col-span-2">
            <x-slot:head>
                <x-ui.table.heading>Department</x-ui.table.heading>
                <x-ui.table.heading>People</x-ui.table.heading>
                <x-ui.table.heading class="w-32"><span class="sr-only">Actions</span></x-ui.table.heading>
            </x-slot:head>
            @forelse ($departments as $department)
                <tr class="transition-colors hover:bg-slate-50">
                    @if ($editing?->is($department))
                        <x-ui.table.cell colspan="3">
                            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="flex flex-wrap items-start gap-2">
                                @csrf
                                @method('PUT')
                                <div class="min-w-48 flex-1">
                                    <label for="edit-name" class="sr-only">Department name</label>
                                    <x-ui.input id="edit-name" name="name" :value="old('name', $department->name)" required autofocus :invalid="$errors->editDepartment->has('name')" />
                                    @if ($errors->editDepartment->has('name'))
                                        <p class="mt-1 text-xs text-red-600">{{ $errors->editDepartment->first('name') }}</p>
                                    @endif
                                </div>
                                <x-ui.button type="submit">Save</x-ui.button>
                                <x-ui.button variant="ghost" :href="route('admin.departments.index')">Cancel</x-ui.button>
                            </form>
                        </x-ui.table.cell>
                    @else
                        <x-ui.table.cell class="font-medium text-slate-900">{{ $department->name }}</x-ui.table.cell>
                        <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-600">{{ $department->users_count }}</x-ui.table.cell>
                        <x-ui.table.cell>
                            <div class="flex justify-end gap-1">
                                <x-ui.button size="sm" variant="ghost" icon="edit" :href="route('admin.departments.index', ['edit' => $department->id])">Rename</x-ui.button>
                                <form method="POST" action="{{ route('admin.departments.destroy', $department) }}"
                                    onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete the '.$department->name.' department?') }})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                                        title="{{ $department->users_count > 0 ? 'Move its people to another department first' : 'Delete' }}" @disabled($department->users_count > 0)>
                                        <span class="sr-only">Delete {{ $department->name }}</span>
                                        <x-ui.icon name="delete" class="text-[18px]" />
                                    </button>
                                </form>
                            </div>
                        </x-ui.table.cell>
                    @endif
                </tr>
            @empty
                <tr><x-ui.table.cell colspan="3" class="text-slate-500">No departments yet.</x-ui.table.cell></tr>
            @endforelse
        </x-ui.table>
    </div>
</x-layouts.app>
