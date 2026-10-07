{{-- Tab bar shared by the service configuration pages. --}}
<x-ui.tabs :items="[
    ['label' => 'Departments', 'href' => route('admin.departments.index'), 'active' => request()->routeIs('admin.departments.*')],
    ['label' => 'Categories', 'href' => route('admin.categories.index'), 'active' => request()->routeIs('admin.categories.*')],
    ['label' => 'Priorities & SLA', 'href' => route('admin.priorities.index'), 'active' => request()->routeIs('admin.priorities.*')],
    ['label' => 'Statuses', 'href' => route('admin.statuses.index'), 'active' => request()->routeIs('admin.statuses.*')],
]" />
