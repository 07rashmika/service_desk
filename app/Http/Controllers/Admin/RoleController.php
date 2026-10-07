<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * The permission grid for the three built-in roles.
 */
class RoleController extends Controller
{
    /**
     * Permissions the Admin role always keeps, so nobody can lock everyone out.
     *
     * @var array<int, PermissionName>
     */
    public const LOCKED_ADMIN_PERMISSIONS = [PermissionName::ManageUsers, PermissionName::ManageRoles];

    public function index(Request $request): View
    {
        $selected = RoleName::tryFrom((string) $request->query('role')) ?? RoleName::Support;

        $roles = Role::query()->withCount('users')->with('permissions')->get()->keyBy('name');

        return view('admin.roles.index', [
            'roles' => collect(RoleName::cases())->map(fn (RoleName $role): array => [
                'role' => $role,
                'users' => $roles[$role->value]->users_count ?? 0,
                'permissions' => $roles[$role->value]?->permissions->count() ?? 0,
            ])->all(),
            'selected' => $selected,
            'granted' => $roles[$selected->value]?->permissions->pluck('name')->all() ?? [],
            'groups' => collect(PermissionName::cases())->groupBy(fn (PermissionName $permission): string => $permission->group()),
            'locked' => $selected === RoleName::Admin
                ? array_map(fn (PermissionName $permission): string => $permission->value, self::LOCKED_ADMIN_PERMISSIONS)
                : [],
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $roleName = RoleName::tryFrom($role) ?? abort(404);

        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(PermissionName::class)],
        ]);

        $permissions = collect($validated['permissions'] ?? []);

        if ($roleName === RoleName::Admin) {
            $permissions = $permissions->merge(array_map(fn (PermissionName $permission): string => $permission->value, self::LOCKED_ADMIN_PERMISSIONS));
        }

        Role::findByName($roleName->value)->syncPermissions($permissions->unique()->values()->all());

        return redirect()
            ->route('admin.roles.index', ['role' => $roleName->value])
            ->with('success', "Permissions for {$roleName->label()} saved. They apply on everyone's next page load.");
    }
}
