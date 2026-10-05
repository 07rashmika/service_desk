<?php

namespace App\Models;

use App\Enums\Palette;
use App\Enums\TicketState;
use Database\Factories\TicketStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A step in the ticket workflow. The slug is fixed (see TicketState); the name
 * and colour can be changed by admins.
 */
#[Fillable(['name', 'slug', 'color', 'sort_order', 'is_final'])]
class TicketStatus extends Model
{
    /** @use HasFactory<TicketStatusFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'slug' => TicketState::class,
            'color' => Palette::class,
            'sort_order' => 'integer',
            'is_final' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    /**
     * Find the status row for a workflow state.
     */
    public static function for(TicketState $state): self
    {
        return static::query()->where('slug', $state)->firstOrFail();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order');
    }
}
