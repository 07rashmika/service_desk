<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

class Navigation extends Component
{
    /**
     * Sidebar sections. An item is only shown once its route exists and, when it
     * declares a `can` ability, the current user is allowed to use it.
     *
     * @var array<int, array{heading: ?string, items: array<int, array{label: string, icon: string, route: string, active?: string, can?: string}>}>
     */
    protected array $sections = [
        [
            'heading' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'grid_view', 'route' => 'dashboard'],
                ['label' => 'My Tickets', 'icon' => 'inbox', 'route' => 'tickets.index', 'active' => 'tickets.show', 'can' => 'tickets.view-own'],
                ['label' => 'New Ticket', 'icon' => 'add_circle', 'route' => 'tickets.create', 'can' => 'tickets.create'],
            ],
        ],
        [
            'heading' => 'Support',
            'items' => [
                ['label' => 'Ticket Queue', 'icon' => 'confirmation_number', 'route' => 'support.tickets.index', 'active' => 'support.tickets.show', 'can' => 'tickets.view-all'],
                ['label' => 'My Assigned', 'icon' => 'assignment_ind', 'route' => 'support.tickets.assigned', 'can' => 'tickets.view-all'],
            ],
        ],
        [
            'heading' => 'Management',
            'items' => [
                ['label' => 'Users', 'icon' => 'group', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'can' => 'users.manage'],
                ['label' => 'Technicians', 'icon' => 'engineering', 'route' => 'admin.technicians.index', 'can' => 'users.manage'],
                ['label' => 'Departments', 'icon' => 'apartment', 'route' => 'admin.departments.index', 'active' => 'admin.departments.*', 'can' => 'settings.manage'],
                ['label' => 'Categories', 'icon' => 'category', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'can' => 'settings.manage'],
                ['label' => 'Priorities & SLA', 'icon' => 'timer', 'route' => 'admin.priorities.index', 'active' => 'admin.priorities.*', 'can' => 'settings.manage'],
                ['label' => 'Statuses', 'icon' => 'rule', 'route' => 'admin.statuses.index', 'active' => 'admin.statuses.*', 'can' => 'settings.manage'],
            ],
        ],
        [
            'heading' => 'Insights & Security',
            'items' => [
                ['label' => 'Reports', 'icon' => 'monitoring', 'route' => 'admin.reports.index', 'can' => 'reports.view'],
                ['label' => 'Roles & Permissions', 'icon' => 'admin_panel_settings', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'can' => 'roles.manage'],
                ['label' => 'Activity Log', 'icon' => 'history', 'route' => 'admin.activity.index', 'can' => 'activity.view'],
            ],
        ],
        [
            'heading' => 'Account',
            'items' => [
                ['label' => 'Notifications', 'icon' => 'notifications', 'route' => 'notifications.index'],
                ['label' => 'Profile', 'icon' => 'account_circle', 'route' => 'profile.edit'],
            ],
        ],
    ];

    /**
     * The sections visible to the current user, with empty sections removed.
     *
     * @return array<int, array{heading: ?string, items: array<int, array{label: string, icon: string, href: string, active: bool}>}>
     */
    public function visibleSections(): array
    {
        $user = auth()->user();

        return collect($this->sections)
            ->map(fn (array $section): array => [
                'heading' => $section['heading'],
                'items' => collect($section['items'])
                    ->filter(fn (array $item): bool => Route::has($item['route'])
                        && (! isset($item['can']) || $user?->can($item['can'])))
                    ->map(fn (array $item): array => [
                        'label' => $item['label'],
                        'icon' => $item['icon'],
                        'href' => route($item['route']),
                        'active' => request()->routeIs($item['route'], $item['active'] ?? $item['route']),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    public function render(): View|Closure|string
    {
        return view('components.layouts.navigation');
    }
}
