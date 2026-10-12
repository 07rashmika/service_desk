<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\NotificationPreference;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'department_id', 'job_title', 'phone', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Tickets this user reported.
     *
     * @return HasMany<Ticket, $this>
     */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /**
     * Tickets currently assigned to this user as the technician.
     *
     * @return HasMany<Ticket, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function ticketComments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Whether the person wants emails of this kind. Everything is on until they switch it off.
     */
    public function wantsEmail(NotificationPreference $preference): bool
    {
        return (bool) ($this->notification_preferences[$preference->value] ?? true);
    }

    /**
     * The user's main role, used for labels in the UI. Users normally have exactly one.
     */
    public function primaryRole(): ?RoleName
    {
        $roleNames = $this->getRoleNames();

        foreach ([RoleName::Admin, RoleName::Support, RoleName::Employee] as $role) {
            if ($roleNames->contains($role->value)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Active IT staff who can be given tickets: anyone with the IT Support or Admin role.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function technicians(Builder $query): void
    {
        $query->role([RoleName::Support->value, RoleName::Admin->value])->where('is_active', true);
    }
}
