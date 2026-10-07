<?php

namespace Tests\Concerns;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TicketStatusSeeder;

/**
 * Seeds the statuses and roles ticket tests depend on, and makes users with a role.
 */
trait CreatesTicketUsers
{
    protected function setUpTicketReferenceData(): void
    {
        $this->seed([TicketStatusSeeder::class, RolesAndPermissionsSeeder::class]);
    }

    protected function employee(): User
    {
        return User::factory()->withRole(RoleName::Employee)->create();
    }

    protected function supportAgent(): User
    {
        return User::factory()->withRole(RoleName::Support)->create();
    }

    protected function admin(): User
    {
        return User::factory()->withRole(RoleName::Admin)->create();
    }
}
