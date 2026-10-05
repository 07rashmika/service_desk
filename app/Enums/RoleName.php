<?php

namespace App\Enums;

/**
 * The built-in roles. Each matches a row in Spatie's `roles` table.
 */
enum RoleName: string
{
    case Employee = 'employee';
    case Support = 'support';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Support => 'IT Support',
            self::Admin => 'Admin',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Employee => 'Can create and track their own tickets.',
            self::Support => 'Handles, assigns and resolves tickets.',
            self::Admin => 'Full system management.',
        };
    }

    /**
     * The permissions this role starts with. Admins can change them later.
     *
     * @return array<int, PermissionName>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::Employee => [
                PermissionName::CreateTickets,
                PermissionName::ViewOwnTickets,
                PermissionName::AddComments,
            ],
            self::Support => [
                PermissionName::CreateTickets,
                PermissionName::ViewOwnTickets,
                PermissionName::ViewAllTickets,
                PermissionName::AssignTickets,
                PermissionName::ChangeTicketStatus,
                PermissionName::ResolveTickets,
                PermissionName::AddComments,
                PermissionName::AddInternalNotes,
                PermissionName::ViewUsers,
                PermissionName::ViewReports,
            ],
            self::Admin => PermissionName::cases(),
        };
    }
}
