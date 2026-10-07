<x-layouts.app title="Categories">
    <x-ui.page-header eyebrow="Service configuration" title="Categories" description="What employees choose when they report an issue.">
        <x-slot:actions>
            <x-ui.button icon="add" x-data x-on:click="$dispatch('open-modal', 'create-category')">Add category</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
    <x-admin.settings-tabs />

    <x-ui.table>
        <x-slot:head>
            <x-ui.table.heading class="w-16">Order</x-ui.table.heading>
            <x-ui.table.heading>Category</x-ui.table.heading>
            <x-ui.table.heading>Tickets</x-ui.table.heading>
            <x-ui.table.heading>Available</x-ui.table.heading>
            <x-ui.table.heading class="w-28"><span class="sr-only">Actions</span></x-ui.table.heading>
        </x-slot:head>
        @foreach ($categories as $category)
            <tr @class(['transition-colors hover:bg-slate-50', 'opacity-60' => ! $category->is_active])>
                <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-500">{{ $category->sort_order }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <div class="flex items-center gap-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                            <x-ui.icon :name="$category->icon ?? 'help'" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900">{{ $category->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $category->description }}</p>
                        </div>
                    </div>
                </x-ui.table.cell>
                <x-ui.table.cell class="font-mono text-xs tabular-nums text-slate-600">{{ $category->tickets_count }}</x-ui.table.cell>
                <x-ui.table.cell>
                    <form method="POST" action="{{ route('admin.categories.toggle', $category) }}">
                        @csrf
                        <button type="submit" role="switch" aria-checked="{{ $category->is_active ? 'true' : 'false' }}"
                            class="flex items-center gap-2 text-label text-slate-600" title="{{ $category->is_active ? 'Hide from the Report an issue form' : 'Show in the Report an issue form' }}">
                            <span @class(['relative inline-flex h-5 w-9 shrink-0 rounded-full transition-colors', 'bg-primary-600' => $category->is_active, 'bg-slate-200' => ! $category->is_active])>
                                <span @class(['absolute top-0.5 left-0.5 size-4 rounded-full bg-white shadow-sm transition-transform', 'translate-x-4' => $category->is_active])></span>
                            </span>
                            {{ $category->is_active ? 'On' : 'Off' }}
                        </button>
                    </form>
                </x-ui.table.cell>
                <x-ui.table.cell>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('admin.categories.index', ['edit' => $category->id]) }}" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Edit">
                            <span class="sr-only">Edit {{ $category->name }}</span>
                            <x-ui.icon name="edit" class="text-[18px]" />
                        </a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                            onsubmit="return confirm({{ \Illuminate\Support\Js::from('Delete the '.$category->name.' category?') }})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-40" @disabled($category->tickets_count > 0)
                                title="{{ $category->tickets_count > 0 ? 'Used by tickets. Switch it off instead.' : 'Delete' }}">
                                <span class="sr-only">Delete {{ $category->name }}</span>
                                <x-ui.icon name="delete" class="text-[18px]" />
                            </button>
                        </form>
                    </div>
                </x-ui.table.cell>
            </tr>
        @endforeach
    </x-ui.table>

    <x-ui.modal name="create-category" title="Add category" icon="category" :show="$errors->createCategory->isNotEmpty()">
        <form id="create-category-form" method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            @include('admin.categories.form-fields', ['category' => null, 'bag' => 'createCategory'])
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'create-category')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="create-category-form">Add category</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    @if ($editing)
        <x-ui.modal name="edit-category" :title="'Edit '.$editing->name" icon="edit" show>
            <form id="edit-category-form" method="POST" action="{{ route('admin.categories.update', $editing) }}">
                @csrf
                @method('PUT')
                @include('admin.categories.form-fields', ['category' => $editing, 'bag' => 'editCategory'])
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.categories.index')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="edit-category-form">Save changes</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</x-layouts.app>
