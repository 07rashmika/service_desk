<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /**
     * Update a person's details and replace their role.
     *
     * @param  array{name: string, email: string, job_title?: ?string, phone?: ?string, department_id?: ?int}  $attributes
     */
    public function handle(User $user, array $attributes, RoleName $role): User
    {
        return DB::transaction(function () use ($user, $attributes, $role): User {
            $user->update($attributes);
            $user->syncRoles([$role]);

            return $user;
        });
    }
}
