<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create every permission and the three built-in roles. Permissions are only
     * given to a role when it is first created, so changes made by an admin
     * survive a re-run.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        // DatabaseSeeder disables model events, which Spatie normally uses to clear this cache.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findOrCreate($roleName->value);

            if ($role->wasRecentlyCreated) {
                $role->givePermissionTo(array_map(
                    fn (PermissionName $permission): string => $permission->value,
                    $roleName->defaultPermissions(),
                ));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
