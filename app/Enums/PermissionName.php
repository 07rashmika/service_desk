<?php

namespace App\Enums;

/**
 * Every permission in the system. Each matches a row in Spatie's `permissions`
 * table, and is grouped the same way as the Roles & Permissions admin screen.
 */
enum PermissionName: string
{
    case CreateTickets = 'tickets.create';
    case ViewOwnTickets = 'tickets.view-own';
    case ViewAllTickets = 'tickets.view-all';
    case AssignTickets = 'tickets.assign';
    case ChangeTicketStatus = 'tickets.change-status';
    case ResolveTickets = 'tickets.resolve';
    case DeleteTickets = 'tickets.delete';

    case AddComments = 'comments.create';
    case AddInternalNotes = 'comments.internal';

    case ViewUsers = 'users.view';
    case ManageUsers = 'users.manage';

    case ManageSettings = 'settings.manage';
    case ManageRoles = 'roles.manage';

    case ViewReports = 'reports.view';
    case ExportReports = 'reports.export';
    case ViewActivityLog = 'activity.view';

    public function label(): string
    {
        return match ($this) {
            self::CreateTickets => 'Create tickets',
            self::ViewOwnTickets => 'View own tickets',
            self::ViewAllTickets => 'View all tickets',
            self::AssignTickets => 'Assign & reassign tickets',
            self::ChangeTicketStatus => 'Change ticket status',
            self::ResolveTickets => 'Resolve tickets',
            self::DeleteTickets => 'Delete tickets',
            self::AddComments => 'Add comments',
            self::AddInternalNotes => 'Add internal notes',
            self::ViewUsers => 'View users',
            self::ManageUsers => 'Manage users',
            self::ManageSettings => 'Manage categories, priorities, statuses & departments',
            self::ManageRoles => 'Manage roles & permissions',
            self::ViewReports => 'View dashboards & reports',
            self::ExportReports => 'Export reports to CSV',
            self::ViewActivityLog => 'View activity log',
        };
    }

    /**
     * The section this permission appears under on the Roles & Permissions screen.
     */
    public function group(): string
    {
        return match ($this) {
            self::CreateTickets, self::ViewOwnTickets, self::ViewAllTickets, self::AssignTickets,
            self::ChangeTicketStatus, self::ResolveTickets, self::DeleteTickets => 'Tickets',
            self::AddComments, self::AddInternalNotes => 'Comments',
            self::ViewUsers, self::ManageUsers => 'Users',
            self::ManageSettings, self::ManageRoles => 'Settings & Security',
            self::ViewReports, self::ExportReports, self::ViewActivityLog => 'Reports',
        };
    }
}
