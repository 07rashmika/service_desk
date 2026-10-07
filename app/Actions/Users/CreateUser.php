<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use App\Models\User;
use App\Notifications\AccountCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser
{
    /**
     * Create an account with a random password nobody knows, then email the
     * person a link to choose their own.
     *
     * @param  array{name: string, email: string, job_title?: ?string, phone?: ?string, department_id?: ?int}  $attributes
     */
    public function handle(array $attributes, RoleName $role): User
    {
        $user = DB::transaction(function () use ($attributes, $role): User {
            $user = User::query()->create([
                ...$attributes,
                'password' => Str::password(32),
                'is_active' => true,
            ]);

            $user->assignRole($role);

            return $user;
        });

        $user->notify(new AccountCreated(Password::broker()->createToken($user)));

        return $user;
    }
}
