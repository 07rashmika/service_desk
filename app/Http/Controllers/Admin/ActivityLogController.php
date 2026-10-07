<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The system-wide audit trail: who changed what, and when.
 */
class ActivityLogController extends Controller
{
    /**
     * Filter groups, matched against the start of the event name.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'ticket.' => 'Tickets',
        'user.' => 'Users',
        'role.' => 'Roles & permissions',
        'settings.' => 'Settings',
    ];

    /**
     * @var array<string, string>
     */
    public const PERIODS = ['1' => 'Last 24 hours', '7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'user' => ['nullable', 'string'],
            'type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'period' => ['nullable', Rule::in(array_keys(self::PERIODS))],
        ]);

        $activities = ActivityLog::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('description', 'like', '%'.addcslashes($search, '\\%_').'%'))
            ->when(($filters['user'] ?? null) === 'system', fn (Builder $query) => $query->whereNull('user_id'))
            ->when(ctype_digit((string) ($filters['user'] ?? '')), fn (Builder $query) => $query->where('user_id', (int) $filters['user']))
            ->when($filters['type'] ?? null, fn (Builder $query, string $prefix) => $query->where('event', 'like', $prefix.'%'))
            ->when($filters['period'] ?? null, fn (Builder $query, string $days) => $query->where('created_at', '>=', now()->subDays((int) $days)))
            ->with(['user', 'subject'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.activity.index', [
            'activities' => $activities,
            'people' => User::query()->whereIn('id', ActivityLog::query()->whereNotNull('user_id')->select('user_id'))->orderBy('name')->get(['id', 'name']),
            'types' => self::TYPES,
            'periods' => self::PERIODS,
            'hasFilters' => collect($filters)->filter()->isNotEmpty(),
        ]);
    }
}
