<?php

namespace App\Http\Requests\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the add/edit user form. The same request serves both: on edit the
 * route has a {user}.
 */
class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionName::ManageUsers);
    }

    /**
     * Errors go to a separate bag per form so the right side panel reopens.
     */
    protected function prepareForValidation(): void
    {
        $this->errorBag = $this->editedUser() ? 'editUser' : 'createUser';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->editedUser())],
            'job_title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]*$/'],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'role' => ['required', Rule::enum(RoleName::class)],
        ];
    }

    /**
     * An admin can't take away their own admin role, which could lock everyone out.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->editedUser()?->is($this->user()) && $this->input('role') !== RoleName::Admin->value) {
                    $validator->errors()->add('role', "You can't remove your own Admin role.");
                }
            },
        ];
    }

    public function role(): RoleName
    {
        return RoleName::from($this->validated('role'));
    }

    /**
     * @return array{name: string, email: string, job_title: ?string, phone: ?string, department_id: ?int}
     */
    public function details(): array
    {
        return $this->safe()->only(['name', 'email', 'job_title', 'phone', 'department_id']);
    }

    protected function editedUser(): ?User
    {
        $user = $this->route('user');

        return $user instanceof User ? $user : null;
    }
}
