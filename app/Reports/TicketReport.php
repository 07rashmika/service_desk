<?php

namespace App\Reports;

use App\Http\Controllers\Support\SupportTicketController;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The numbers behind the admin dashboard and the Reports page, for one date range.
 *
 * "Created" counts tickets reported in the range; "resolved" counts tickets resolved
 * in the range, whenever they were reported. SLA compliance compares the resolution
 * time with the deadline (which already includes any time spent waiting on the requester).
 */
class TicketReport
{
    /**
     * Date range presets, in days.
     *
     * @var array<string, string>
     */
    public const PERIODS = ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public function __construct(public readonly int $days = 30, ?CarbonInterface $now = null)
    {
        $now = CarbonImmutable::instance($now ?? now());
        $this->to = $now->endOfDay();
        $this->from = $now->subDays($days - 1)->startOfDay();
    }

    /**
     * The same report for the period just before this one, for the deltas.
     */
    public function previous(): self
    {
        return new self($this->days, $this->from->subDay());
    }

    /**
     * @return Builder<Ticket>
     */
    public function created(): Builder
    {
        return Ticket::query()->whereBetween('tickets.created_at', [$this->from, $this->to]);
    }

    /**
     * @return Builder<Ticket>
     */
    public function resolved(): Builder
    {
        return Ticket::query()->whereBetween('tickets.resolved_at', [$this->from, $this->to]);
    }

    /**
     * @return array{created: int, resolved: int, open: int, averageResolutionHours: ?float, slaCompliance: ?float, firstResponseCompliance: ?float}
     */
    public function summary(): array
    {
        $resolved = $this->resolved()->get(['created_at', 'resolved_at', 'due_at']);
        $responded = $this->created()->whereNotNull('first_response_at')->with('priority:id,response_hours')->get(['created_at', 'first_response_at', 'priority_id']);

        return [
            'created' => $this->created()->count(),
            'resolved' => $resolved->count(),
            'open' => Ticket::query()->inStates(...SupportTicketController::ACTIVE_STATES)->count(),
            'averageResolutionHours' => self::averageHours($resolved),
            'slaCompliance' => self::metShare($resolved),
            'firstResponseCompliance' => $responded->isEmpty() ? null : round(
                $responded->filter(fn (Ticket $ticket): bool => $ticket->first_response_at->lte($ticket->created_at->copy()->addHours($ticket->priority->response_hours)))->count()
                / $responded->count() * 100,
                1,
            ),
        ];
    }

    /**
     * Tickets created and resolved per day, for the line chart.
     *
     * @return array{labels: array<int, string>, dates: array<int, string>, created: array<int, int>, resolved: array<int, int>}
     */
    public function dailyVolume(): array
    {
        $created = $this->created()->pluck('created_at')->countBy(fn (CarbonInterface $at): string => $at->toDateString());
        $resolved = $this->resolved()->pluck('resolved_at')->countBy(fn (CarbonInterface $at): string => $at->toDateString());
        $days = collect(CarbonPeriod::create($this->from, '1 day', $this->to->startOfDay()));

        return [
            'labels' => $days->map(fn (CarbonInterface $day): string => $day->format('M j'))->all(),
            'dates' => $days->map(fn (CarbonInterface $day): string => $day->toDateString())->all(),
            'created' => $days->map(fn (CarbonInterface $day): int => $created->get($day->toDateString(), 0))->all(),
            'resolved' => $days->map(fn (CarbonInterface $day): int => $resolved->get($day->toDateString(), 0))->all(),
        ];
    }

    /**
     * Tickets created in the range, by their current status, in workflow order.
     *
     * @return Collection<int, array{status: TicketStatus, count: int}>
     */
    public function byStatus(): Collection
    {
        $counts = $this->created()->toBase()->selectRaw('status_id, count(*) as aggregate')->groupBy('status_id')->pluck('aggregate', 'status_id');

        return TicketStatus::query()->ordered()->get()
            ->map(fn (TicketStatus $status): array => ['status' => $status, 'count' => (int) ($counts[$status->id] ?? 0)]);
    }

    /**
     * @return Collection<int, array{label: string, count: int}>
     */
    public function byCategory(): Collection
    {
        $counts = $this->created()->toBase()->selectRaw('category_id, count(*) as aggregate')->groupBy('category_id')->pluck('aggregate', 'category_id');

        return TicketCategory::query()->ordered()->get(['id', 'name'])
            ->map(fn (TicketCategory $category): array => ['label' => $category->name, 'count' => (int) ($counts[$category->id] ?? 0)])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->sortByDesc('count')
            ->values();
    }

    /**
     * Tickets created in the range, by the requester's department.
     *
     * @return Collection<int, array{label: string, count: int}>
     */
    public function byDepartment(): Collection
    {
        $counts = $this->created()
            ->join('users', 'users.id', '=', 'tickets.created_by')
            ->toBase()
            ->selectRaw('users.department_id, count(*) as aggregate')
            ->groupBy('users.department_id')
            ->pluck('aggregate', 'department_id');

        $names = Department::query()->pluck('name', 'id');

        return $counts
            ->map(fn ($count, $departmentId): array => ['label' => $names[$departmentId] ?? 'No department', 'count' => (int) $count])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * SLA results for tickets resolved in the range, per priority.
     *
     * @return Collection<int, array{priority: TicketPriority, resolved: int, compliance: ?float, averageResolutionHours: ?float}>
     */
    public function slaByPriority(): Collection
    {
        $resolved = $this->resolved()->get(['priority_id', 'created_at', 'resolved_at', 'due_at'])->groupBy('priority_id');

        return TicketPriority::query()->ordered()->get()->reverse()->values()
            ->map(fn (TicketPriority $priority): array => [
                'priority' => $priority,
                'resolved' => $resolved->get($priority->id, collect())->count(),
                'compliance' => self::metShare($resolved->get($priority->id, collect())),
                'averageResolutionHours' => self::averageHours($resolved->get($priority->id, collect())),
            ]);
    }

    /**
     * Workload and results for every technician.
     *
     * @return Collection<int, array{technician: User, active: int, resolved: int, compliance: ?float, averageResolutionHours: ?float}>
     */
    public function technicians(): Collection
    {
        $resolved = $this->resolved()->whereNotNull('assigned_to')->get(['assigned_to', 'created_at', 'resolved_at', 'due_at'])->groupBy('assigned_to');

        return User::query()
            ->technicians()
            ->withCount(['assignedTickets as active_count' => fn (Builder $query) => $query->inStates(...SupportTicketController::ACTIVE_STATES)])
            ->orderBy('name')
            ->get()
            ->map(fn (User $technician): array => [
                'technician' => $technician,
                'active' => $technician->active_count,
                'resolved' => $resolved->get($technician->id, collect())->count(),
                'compliance' => self::metShare($resolved->get($technician->id, collect())),
                'averageResolutionHours' => self::averageHours($resolved->get($technician->id, collect())),
            ])
            ->sortByDesc('resolved')
            ->values();
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     */
    protected static function averageHours(Collection $tickets): ?float
    {
        return $tickets->isEmpty()
            ? null
            : round($tickets->avg(fn (Ticket $ticket): float => $ticket->created_at->diffInMinutes($ticket->resolved_at)) / 60, 1);
    }

    /**
     * Share of resolved tickets that met their deadline, as a percentage.
     *
     * @param  Collection<int, Ticket>  $tickets
     */
    protected static function metShare(Collection $tickets): ?float
    {
        $withDeadline = $tickets->filter(fn (Ticket $ticket): bool => $ticket->due_at !== null);

        return $withDeadline->isEmpty()
            ? null
            : round($withDeadline->filter(fn (Ticket $ticket): bool => $ticket->resolved_at->lte($ticket->due_at))->count() / $withDeadline->count() * 100, 1);
    }

    /**
     * How a figure moved compared with the previous period.
     *
     * @return array{text: string, tone: string}|null
     */
    public static function delta(int|float|null $current, int|float|null $previous, bool $higherIsBetter, string $unit = '%'): ?array
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $change = $unit === '%' && $previous != 0
            ? round(($current - $previous) / $previous * 100)
            : round($current - $previous, 1);

        if ($unit === '%' && $previous == 0) {
            return null;
        }

        $tone = match (true) {
            $change == 0 => 'neutral',
            ($change > 0) === $higherIsBetter => 'good',
            default => 'bad',
        };

        return ['text' => ($change > 0 ? '+' : '').$change.$unit, 'tone' => $tone];
    }
}
