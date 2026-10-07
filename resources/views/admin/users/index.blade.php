@php
    $roleColors = ['employee' => 'slate', 'support' => 'primary', 'admin' => 'violet'];
    $listQuery = request()->only(['search', 'role', 'department', 'status', 'page']);
@endphp

<x-layouts.app title="Users">
    <x-ui.page-header eyebrow="Access & identity" title="Users & Directory"
        description="Create accounts, set each person's role and department, and deactivate people who leave.">
        <x-slot:actions>
            <x-ui.button icon="person_add" x-data x-on:click="$dispatch('open-modal', 'create-user')">Add user</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <x-ui.stat-card label="Active staff" :value="$stats['active']" icon="group" :href="route('admin.users.index', ['status' => 'active'])" />
        <x-ui.stat-card label="IT Support" :value="$stats['support']" icon="support_agent" :href="route('admin.technicians.index')" />
        <x-ui.stat-card label="Admins" :value="$stats['admins']" icon="admin_panel_settings" :href="route('admin.users.index', ['role' => 'admin'])" />
        <x-ui.stat-card label="Inactive accounts" :value="$stats['inactive']" icon="person_off" :tone="$stats['inactive'] > 0 ? 'warning' : 'default'"
            :href="route('admin.users.index', ['status' => 'inactive'])" />
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-4 lg:flex-row lg:items-center">
        <div class="lg:flex-1">
            <label for="search" class="sr-only">Search users</label>
            <x-ui.input name="search" type="search" icon="search" :value="request('search')" placeholder="Search by name or email" />
        </div>
        <div class="grid grid-cols-3 gap-3 lg:flex">
            <div class="lg:w-40">
                <label for="role" class="sr-only">Role</label>
                <x-ui.select name="role" onchange="this.form.requestSubmit()">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="lg:w-48">
                <label for="department" class="sr-only">Department</label>
                <x-ui.select name="department" onchange="this.form.requestSubmit()">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="lg:w-36">
                <label for="status" class="sr-only">Status</label>
                <x-ui.select name="status" onchange="this.form.requestSubmit()">
                    <option value="">Any status</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </x-ui.select>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="secondary" icon="filter_list">Apply</x-ui.button>
            @if ($hasFilters)
                <x-ui.button variant="ghost" icon="close" :href="route('admin.users.index')">Clear</x-ui.button>
            @endif
        </div>
    </form>

    @if ($users->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state icon="person_search" title="No users found" description="Nobody matches your search and filters.">
                <x-ui.button variant="secondary" :href="route('admin.users.index')">Clear filters</x-ui.button>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <x-ui.table>
            <x-slot:head>
                <x-ui.table.heading>User</x-ui.table.heading>
                <x-ui.table.heading>Role</x-ui.table.heading>
                <x-ui.table.heading class="hidden md:table-cell">Department</x-ui.table.heading>
                <x-ui.table.heading>Status</x-ui.table.heading>
                <x-ui.table.heading class="hidden lg:table-cell">Tickets</x-ui.table.heading>
                <x-ui.table.heading class="hidden lg:table-cell">Last sign-in</x-ui.table.heading>
                <x-ui.table.heading class="w-12"><span class="sr-only">Actions</span></x-ui.table.heading>
            </x-slot:head>

            @foreach ($users as $user)
                @php($role = $user->primaryRole())
                <tr @class(['transition-colors hover:bg-slate-50', 'opacity-60' => ! $user->is_active])>
                    <x-ui.table.cell>
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :name="$user->name" />
                            <div class="min-w-0">
                                <p class="font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user()))
                                        <span class="text-xs font-normal text-slate-500">(you)</span>
                                    @endif
                                </p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </x-ui.table.cell>
                    <x-ui.table.cell>
                        @if ($role)
                            <x-ui.badge :color="$roleColors[$role->value]">{{ $role->label() }}</x-ui.badge>
                        @else
                            <span class="text-xs text-slate-400 italic">No role</span>
                        @endif
                    </x-ui.table.cell>
                    <x-ui.table.cell class="hidden text-slate-600 md:table-cell">{{ $user->department?->name ?? '—' }}</x-ui.table.cell>
                    <x-ui.table.cell>
                        <span @class(['inline-flex items-center gap-1.5 text-label', 'text-emerald-700' => $user->is_active, 'text-slate-500' => ! $user->is_active])>
                            <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $user->is_active, 'bg-slate-400' => ! $user->is_active])></span>
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </x-ui.table.cell>
                    <x-ui.table.cell class="hidden font-mono text-xs tabular-nums text-slate-600 lg:table-cell">{{ $user->created_tickets_count }}</x-ui.table.cell>
                    <x-ui.table.cell class="hidden whitespace-nowrap text-slate-600 lg:table-cell">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</x-ui.table.cell>
                    <x-ui.table.cell>
                        <x-ui.dropdown align="right" width="w-60">
                            <x-slot:trigger>
                                <button type="button" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-900">
                                    <span class="sr-only">Actions for {{ $user->name }}</span>
                                    <x-ui.icon name="more_vert" />
                                </button>
                            </x-slot:trigger>
                            <x-ui.dropdown-link :href="route('admin.users.index', [...$listQuery, 'edit' => $user->id])" icon="edit">Edit details &amp; role</x-ui.dropdown-link>
                            @if ($user->is_active)
                                <form method="POST" action="{{ route('admin.users.password-reset', $user) }}">
                                    @csrf
                                    <x-ui.dropdown-link as="button" type="submit" icon="lock_reset">Send password reset link</x-ui.dropdown-link>
                                </form>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                                        @csrf
                                        <x-ui.dropdown-link as="button" type="submit" icon="person_off" danger>Deactivate</x-ui.dropdown-link>
                                    </form>
                                @endunless
                            @else
                                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                                    @csrf
                                    <x-ui.dropdown-link as="button" type="submit" icon="person_check">Reactivate</x-ui.dropdown-link>
                                </form>
                            @endif
                        </x-ui.dropdown>
                    </x-ui.table.cell>
                </tr>
            @endforeach

            @if ($users->hasPages())
                <x-slot:footer>{{ $users->links() }}</x-slot:footer>
            @endif
        </x-ui.table>
    @endif

    <x-ui.slide-over name="create-user" title="Add user" description="They'll get an email with a link to set their own password."
        :show="$errors->createUser->isNotEmpty()">
        <x-admin.user-form bag="createUser" form-id="create-user-form" :departments="$departments" :roles="$roles" />
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'create-user')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="create-user-form" icon="send">Create &amp; send invite</x-ui.button>
        </x-slot:footer>
    </x-ui.slide-over>

    @if ($editing)
        <x-ui.slide-over name="edit-user" :title="'Edit '.$editing->name" :description="$editing->email" show>
            <x-admin.user-form :user="$editing" bag="editUser" form-id="edit-user-form" :departments="$departments" :roles="$roles" />
            <x-slot:footer>
                <x-ui.button variant="secondary" :href="route('admin.users.index', $listQuery)">Cancel</x-ui.button>
                <x-ui.button type="submit" form="edit-user-form">Save changes</x-ui.button>
            </x-slot:footer>
        </x-ui.slide-over>
    @endif
</x-layouts.app>
