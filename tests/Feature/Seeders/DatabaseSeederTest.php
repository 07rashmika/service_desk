<?php

namespace Tests\Feature\Seeders;

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_reference_data_and_demo_tickets(): void
    {
        $this->seed();

        $this->assertSame(7, Department::count());
        $this->assertSame(6, TicketCategory::count());
        $this->assertSame(6, TicketStatus::count());
        $this->assertSame(4, TicketPriority::count());
        $this->assertSame(50, Ticket::count());
        $this->assertTrue(User::where('email', 'employee@servicedesk.test')->first()->createdTickets()->exists());
        $this->assertTrue(User::where('email', 'support@servicedesk.test')->first()->assignedTickets()->exists());
        $this->assertSame(RoleName::Admin, User::where('email', 'admin@servicedesk.test')->first()->primaryRole());
        $this->assertSame(0, User::doesntHave('roles')->count());
    }

    public function test_demo_ticket_timelines_are_in_order(): void
    {
        $this->seed();

        foreach (Ticket::all() as $ticket) {
            $this->assertFalse($ticket->created_at->isFuture(), "{$ticket->reference} was created in the future");

            if ($ticket->resolved_at) {
                $this->assertTrue($ticket->resolved_at->gte($ticket->created_at), "{$ticket->reference} was resolved before it was created");
            }

            if ($ticket->closed_at) {
                $this->assertTrue($ticket->closed_at->gte($ticket->resolved_at), "{$ticket->reference} was closed before it was resolved");
            }
        }

        $this->assertFalse(TicketComment::where('created_at', '>', now())->exists());
    }

    public function test_reference_seeders_can_run_again_without_duplicates(): void
    {
        $seeders = [DepartmentSeeder::class, TicketCategorySeeder::class, TicketStatusSeeder::class, TicketPrioritySeeder::class];

        $this->seed($seeders);
        TicketPriority::where('slug', 'high')->update(['resolution_hours' => 6]);
        $this->seed($seeders);

        $this->assertSame(7, Department::count());
        $this->assertSame(6, TicketCategory::count());
        $this->assertSame(6, TicketStatus::count());
        $this->assertSame(4, TicketPriority::count());
        $this->assertSame(6, TicketPriority::where('slug', 'high')->value('resolution_hours'));
    }
}
