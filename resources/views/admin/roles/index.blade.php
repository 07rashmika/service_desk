<x-layouts.app title="Roles & permissions">
    <x-ui.page-header eyebrow="Security" title="Roles & Permissions"
        description="Choose what each role can do. Everyone with the role gets the change on their next page load." />

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <nav class="flex flex-col gap-3" aria-label="Roles">
            @foreach ($roles as $item)
                @php($isSelected = $item['role'] === $selected)
                <a href="{{ route('admin.roles.index', ['role' => $item['role']->value]) }}" @if ($isSelected) aria-current="page" @endif
                    @class([
                        'flex flex-col gap-1 rounded-lg border bg-white p-4 transition-colors',
                        'border-primary-600 ring-1 ring-primary-600' => $isSelected,
                        'border-slate-200 hover:border-slate-300' => ! $isSelected,
                    ])>
                    <span class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-slate-900">{{ $item['role']->label() }}</span>
                        <span class="text-xs text-slate-500">{{ $item['users'] }} {{ str('user')->plural($item['users']) }}</span>
                    </span>
                    <span class="text-label text-slate-500">{{ $item['role']->description() }}</span>
                    <span class="text-xs font-medium text-primary-700">{{ $item['permissions'] }} of {{ count(\App\Enums\PermissionName::cases()) }} permissions</span>
                </a>
            @endforeach
        </nav>

        <form method="POST" action="{{ route('admin.roles.update', $selected->value) }}" class="flex flex-col gap-4 lg:col-span-2"
            x-data="{ dirty: false }" x-on:change="dirty = true">
            @csrf
            @method('PUT')

            @foreach ($groups as $group => $permissions)
                <x-ui.card :title="$group" x-data>
                    <x-slot:actions>
                        <button type="button" class="text-label font-medium text-primary-600 hover:text-primary-700"
                            x-on:click="$root.querySelectorAll('input[type=checkbox]:not(:disabled)').forEach((box) => box.checked = true); dirty = true">
                            Select all
                        </button>
                    </x-slot:actions>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($permissions as $permission)
                            @php($isLocked = in_array($permission->value, $locked, true))
                            <label @class(['flex items-start gap-3', 'cursor-not-allowed' => $isLocked, 'cursor-pointer' => ! $isLocked])>
                                <input type="checkbox" name="permissions[]" value="{{ $permission->value }}"
                                    class="mt-0.5 size-4 shrink-0 rounded border-slate-300 accent-primary-600"
                                    @checked(in_array($permission->value, $granted, true) || $isLocked) @disabled($isLocked)>
                                <span class="flex flex-col">
                                    <span class="text-label font-medium text-slate-900">{{ $permission->label() }}</span>
                                    <span class="font-mono text-xs text-slate-400">{{ $permission->value }}</span>
                                    @if ($isLocked)
                                        <span class="text-xs text-slate-500">Always on for Admin, so nobody gets locked out.</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach

            <div class="sticky bottom-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-popover">
                <p class="flex items-center gap-2 text-label text-slate-600">
                    <span x-cloak x-show="dirty" class="size-2 rounded-full bg-orange-500"></span>
                    <span x-text="dirty ? 'Unsaved changes to {{ $selected->label() }}' : 'Editing {{ $selected->label() }}'">Editing {{ $selected->label() }}</span>
                </p>
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" :href="route('admin.roles.index', ['role' => $selected->value])">Discard</x-ui.button>
                    <x-ui.button type="submit" icon="check">Save permissions</x-ui.button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
