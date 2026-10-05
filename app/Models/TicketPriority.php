<?php

namespace App\Models;

use App\Enums\Palette;
use Database\Factories\TicketPriorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * How urgent a ticket is, with its SLA targets. A higher level is more urgent.
 */
#[Fillable(['name', 'slug', 'description', 'color', 'level', 'response_hours', 'resolution_hours'])]
class TicketPriority extends Model
{
    /** @use HasFactory<TicketPriorityFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'color' => Palette::class,
            'level' => 'integer',
            'response_hours' => 'integer',
            'resolution_hours' => 'integer',
        ];
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    /**
     * Least urgent first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('level');
    }
}
