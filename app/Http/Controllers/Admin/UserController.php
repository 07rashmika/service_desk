<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(RoleName::class)],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'edit' => ['nullable', 'integer'],
        ]);

        $users = User::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $like = '%'.addcslashes($search, '\\%_').'%';
                $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->role($role))
            ->when($filters['department'] ?? null, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->with(['roles', 'department'])
            ->withCount('createdTickets')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => [
                'active' => User::query()->where('is_active', true)->count(),
                'support' => User::query()->role(RoleName::Support->value)->where('is_active', true)->count(),
                'admins' => User::query()->role(RoleName::Admin->value)->where('is_active', true)->count(),
                'inactive' => User::query()->where('is_active', false)->count(),
            ],
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'roles' => RoleName::cases(),
            'editing' => isset($filters['edit']) ? User::query()->with('roles')->find($filters['edit']) : null,
            'hasFilters' => collect($filters)->except('edit')->filter()->isNotEmpty(),
        ]);
    }

    public function store(SaveUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $user = $createUser->handle($request->details(), $request->role());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Account created for {$user->name}. We've emailed {$user->email} a link to set their password.");
    }

    public function update(SaveUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->details(), $request->role());

        return redirect()
            // The list's filters travel in the query string; the form body has its own `role` field.
            ->route('admin.users.index', Arr::only($request->query(), ['search', 'role', 'department', 'status', 'page']))
            ->with('success', "{$user->name}'s details were saved.");
    }

    /**
     * Deactivated users can't sign in, and are signed out on their next click.
     * Their tickets and history are kept.
     */
    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't deactivate your own account.");
        }

        if ($user->hasRole(RoleName::Admin) && User::query()->role(RoleName::Admin->value)->where('is_active', true)->count() <= 1) {
            return back()->with('error', "{$user->name} is the only active admin. Make someone else an admin first.");
        }

        $user->update(['is_active' => false]);

        return back()->with('success', "{$user->name} was deactivated and can no longer sign in.");
    }

    public function activate(User $user): RedirectResponse
    {
        $user->update(['is_active' => true]);

        return back()->with('success', "{$user->name} can sign in again.");
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', "Password reset link sent to {$user->email}.")
            : back()->with('error', 'A reset link was sent to this person very recently. Try again in a minute.');
    }
}
