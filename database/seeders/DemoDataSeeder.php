<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\TicketState;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Demo users and a realistic set of tickets for local development.
 * Every demo account uses the password "password".
 */
class DemoDataSeeder extends Seeder
{
    /**
     * How many demo tickets to create in each state.
     *
     * @var array<string, int>
     */
    protected const TICKETS_PER_STATE = [
        'open' => 8,
        'assigned' => 6,
        'in_progress' => 10,
        'waiting_for_user' => 5,
        'resolved' => 8,
        'closed' => 13,
    ];

    /**
     * Realistic issues as [title, description, category, priority slug].
     *
     * @var array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    protected const ISSUES = [
        ['Outlook keeps asking for my password', 'Every time I open Outlook it prompts for my Microsoft 365 credentials again, even right after I enter them. It started after yesterday\'s update.', 'Email & Accounts', 'high'],
        ['Laptop won\'t connect to office Wi-Fi', 'My laptop sees the office network but fails to connect with "Can\'t connect to this network". My phone connects fine.', 'Network', 'high'],
        ['VPN disconnects every 15 minutes', 'When working from home the VPN drops roughly every 15 minutes and I have to reconnect. This interrupts video calls.', 'Network', 'medium'],
        ['Request second external monitor', 'I\'d like a second 27" monitor for my desk on floor 3. My current setup only has the laptop screen and one monitor.', 'Hardware', 'low'],
        ['Printer on floor 4 prints blank pages', 'The printer near the kitchen on floor 4 pulls paper through but the pages come out blank. Other printers work.', 'Printer', 'medium'],
        ['Need access to the Finance shared drive', 'I joined the Finance team this week and need read/write access to the Finance shared drive for month-end reports.', 'Email & Accounts', 'medium'],
        ['Excel crashes when opening large files', 'Excel freezes and closes whenever I open the quarterly sales workbook (about 40 MB). Smaller files open fine.', 'Software', 'high'],
        ['Laptop battery drains very quickly', 'My laptop battery goes from full to empty in about an hour, even with only the browser open. It used to last most of the day.', 'Hardware', 'medium'],
        ['MFA codes not arriving on new phone', 'I switched phones and the authenticator app no longer receives sign-in approvals. I\'m locked out of email on my laptop.', 'Email & Accounts', 'critical'],
        ['Install Figma desktop app', 'Please install the Figma desktop app on my laptop. I don\'t have admin rights to install it myself.', 'Software', 'low'],
        ['Docking station display flickering', 'Both monitors connected to my USB-C dock flicker every few seconds. Connecting directly to the laptop works fine.', 'Hardware', 'medium'],
        ['Meeting room screen not detecting laptop', 'The screen in meeting room B2 shows "No signal" when I connect via HDMI. Tried two different laptops.', 'Hardware', 'high'],
        ['Cannot scan to email from copier', 'Scan to email on the main copier fails with "SMTP error". Scanning to USB still works.', 'Printer', 'low'],
        ['Shared calendar not syncing', 'Changes to the team\'s shared calendar don\'t show up for me until hours later. Colleagues see them immediately.', 'Email & Accounts', 'medium'],
        ['Slow internet on floor 2', 'Since Monday the internet on floor 2 is very slow; websites take 20+ seconds to load. Several people on the floor have the same issue.', 'Network', 'critical'],
        ['Keyboard keys not working', 'The E and R keys on my laptop keyboard stopped working. An external keyboard works fine.', 'Hardware', 'medium'],
        ['Request Adobe Acrobat Pro licence', 'I need Adobe Acrobat Pro to edit and sign contracts. Approved by my manager.', 'Software', 'low'],
        ['Locked out after password expiry', 'My password expired over the weekend and now my account is locked. I can\'t sign in to anything.', 'Email & Accounts', 'critical'],
        ['Teams camera not detected', 'Microsoft Teams says no camera was found, but the camera works in other apps.', 'Software', 'medium'],
        ['New starter laptop setup', 'We have a new team member starting next Monday who needs a laptop, accounts and access to our team drive.', 'Other', 'medium'],
        ['Phone extension not ringing', 'Calls to my desk extension go straight to voicemail without ringing.', 'Other', 'low'],
        ['Database backup job failed', 'Last night\'s backup job for the reporting database failed with a timeout. Please check it before tonight\'s run.', 'Software', 'high'],
        ['Website blocked by firewall', 'A supplier\'s website we need for orders is blocked by the web filter as "uncategorised".', 'Network', 'low'],
        ['Mouse not connecting via Bluetooth', 'My wireless mouse won\'t pair with my laptop anymore. New batteries didn\'t help.', 'Hardware', 'low'],
    ];

    protected const TECHNICIAN_REPLIES = [
        'Thanks for reporting this. I\'m looking into it now and will update you shortly.',
        'I\'ve checked the logs on our side and can see the problem. Working on a fix.',
        'I\'ve pushed a configuration change to your device. Please restart and let me know if it helps.',
        'I can drop by your desk in about 30 minutes to take a look, if that works for you.',
    ];

    protected const QUESTIONS_FOR_USER = [
        'Could you send a screenshot of the error message you see?',
        'Can you try restarting your laptop and tell me if the problem is still there?',
        'Which room or desk are you at? I\'d like to check the equipment in person.',
        'Could you confirm whether this also happens on another device?',
    ];

    protected const EMPLOYEE_REPLIES = [
        'Thanks! I\'ve tried that and I\'ll let you know how it goes.',
        'It\'s still happening, unfortunately. It happened again this morning.',
        'That worked, thank you for the quick help!',
        'Attached is the screenshot you asked for.',
    ];

    protected const INTERNAL_NOTES = [
        'Same issue reported by two others this week. Might be related to the latest update rollout.',
        'Checked with the network team; no outages on their side.',
        'Device is still under warranty. Replacement can be ordered if the fix doesn\'t work.',
        'Escalating to the vendor if this happens again.',
    ];

    protected const SOLUTIONS = [
        'Cleared the cached credentials and re-registered the device. Confirmed the user can sign in normally.',
        'Updated the driver and firmware, then reset the network settings. The connection is now stable.',
        'Replaced the faulty hardware and tested it with the user.',
        'Granted the required access and confirmed it with the user.',
        'Reinstalled the application with the latest version and restored the user\'s settings.',
    ];

    public function run(): void
    {
        $departments = Department::query()->pluck('id', 'name');
        $categories = TicketCategory::query()->pluck('id', 'name');
        $priorities = TicketPriority::query()->get()->keyBy('slug');
        $statuses = TicketStatus::query()->get()->keyBy(fn (TicketStatus $status): string => $status->slug->value);

        $admin = $this->demoUser('Sarah Jenkins', 'admin@servicedesk.test', 'System Administrator', $departments['IT Operations'], RoleName::Admin);
        $technicians = collect([
            $this->demoUser('Kasun Perera', 'support@servicedesk.test', 'IT Support Specialist', $departments['IT Operations'], RoleName::Support),
            $this->demoUser('Marcus Chen', 'marcus.chen@servicedesk.test', 'IT Support Specialist', $departments['IT Operations'], RoleName::Support),
            $this->demoUser('Elena Rostova', 'elena.rostova@servicedesk.test', 'Network Engineer', $departments['IT Operations'], RoleName::Support),
            $this->demoUser('Kenji Sato', 'kenji.sato@servicedesk.test', 'IT Support Technician', $departments['IT Operations'], RoleName::Support),
        ]);
        $demoEmployee = $this->demoUser('Nimal Perera', 'employee@servicedesk.test', 'Product Designer', $departments['Product & Design'], RoleName::Employee);

        $otherDepartments = $departments->except('IT Operations')->values();
        $employees = User::factory()
            ->withRole(RoleName::Employee)
            ->count(15)
            ->sequence(fn ($sequence) => ['department_id' => $otherDepartments[$sequence->index % $otherDepartments->count()]])
            ->create()
            ->push($demoEmployee);
        User::factory()->withRole(RoleName::Employee)->inactive()->create(['department_id' => $otherDepartments->first()]);

        $issueIndex = 0;

        foreach (self::TICKETS_PER_STATE as $slug => $count) {
            $state = TicketState::from($slug);

            for ($i = 0; $i < $count; $i++) {
                [$title, $description, $category, $priority] = self::ISSUES[$issueIndex % count(self::ISSUES)];
                $issueIndex++;

                // Give the demo employee a share of the tickets so their pages have data.
                $creator = $i % 3 === 0 ? $demoEmployee : $employees->random();

                $this->createTicket(
                    state: $state,
                    status: $statuses[$slug],
                    attributes: [
                        'title' => $title,
                        'description' => $description,
                        'category_id' => $categories[$category],
                        'priority_id' => $priorities[$priority]->id,
                        'created_by' => $creator->id,
                    ],
                    priority: $priorities[$priority],
                    creator: $creator,
                    technician: $technicians->random(),
                    admin: $admin,
                );
            }
        }
    }

    protected function demoUser(string $name, string $email, string $jobTitle, int $departmentId, RoleName $role): User
    {
        return User::factory()->withRole($role)->create([
            'name' => $name,
            'email' => $email,
            'job_title' => $jobTitle,
            'department_id' => $departmentId,
        ]);
    }

    /**
     * Create one ticket with a believable timeline of assignment, comments and resolution.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createTicket(
        TicketState $state,
        TicketStatus $status,
        array $attributes,
        TicketPriority $priority,
        User $creator,
        User $technician,
        User $admin,
    ): void {
        $now = CarbonImmutable::now();
        $order = $state->sortOrder();

        // Most active tickets are still within their SLA and a few are overdue;
        // finished tickets are spread over the last month.
        $slaMinutes = $priority->resolution_hours * 60;
        $createdAt = match (true) {
            $order >= TicketState::Resolved->sortOrder() => $now->subDays(fake()->numberBetween(4, 30))->subMinutes(fake()->numberBetween(0, 600)),
            fake()->boolean(80) => $now->subMinutes(fake()->numberBetween(15, (int) ($slaMinutes * 0.8))),
            default => $now->subMinutes(fake()->numberBetween($slaMinutes + 30, $slaMinutes + 3 * 24 * 60)),
        };

        $assignedAt = $this->notAfter($createdAt->addMinutes(fake()->numberBetween(10, 90)), $now);
        $respondedAt = $this->notAfter($assignedAt->addMinutes(fake()->numberBetween(5, 60)), $now);
        $resolvedAt = $this->notAfter($respondedAt->addHours(fake()->numberBetween(1, 48)), $now);
        $closedAt = $this->notAfter($resolvedAt->addHours(fake()->numberBetween(2, 72)), $now);

        $isAssigned = $order >= TicketState::Assigned->sortOrder();
        $isStarted = $order >= TicketState::InProgress->sortOrder();
        $isResolved = $order >= TicketState::Resolved->sortOrder();

        $ticket = Ticket::factory()->create([
            ...$attributes,
            'status_id' => $status->id,
            'assigned_to' => $isAssigned ? $technician->id : null,
            'solution' => $isResolved ? fake()->randomElement(self::SOLUTIONS) : null,
            'due_at' => $createdAt->addHours($priority->resolution_hours),
            'first_response_at' => $isStarted ? $respondedAt : null,
            'resolved_at' => $isResolved ? $resolvedAt : null,
            'closed_at' => $state === TicketState::Closed ? $closedAt : null,
            'created_at' => $createdAt,
            'updated_at' => match (true) {
                $state === TicketState::Closed => $closedAt,
                $isResolved => $resolvedAt,
                $isStarted => $respondedAt,
                $isAssigned => $assignedAt,
                default => $createdAt,
            },
        ]);

        if (! $isAssigned) {
            return;
        }

        TicketAssignment::factory()->create([
            'ticket_id' => $ticket->id,
            'assigned_to' => $technician->id,
            'assigned_by' => $admin->id,
            'created_at' => $assignedAt,
            'updated_at' => $assignedAt,
        ]);

        if (! $isStarted) {
            return;
        }

        $comments = [
            [$technician, fake()->randomElement(self::TECHNICIAN_REPLIES), false, $respondedAt],
        ];

        if (fake()->boolean(40)) {
            $comments[] = [$technician, fake()->randomElement(self::INTERNAL_NOTES), true, $respondedAt->addMinutes(5)];
        }

        if ($state === TicketState::WaitingForUser) {
            $comments[] = [$technician, fake()->randomElement(self::QUESTIONS_FOR_USER), false, $respondedAt->addMinutes(20)];
        } elseif (fake()->boolean(70)) {
            $comments[] = [$creator, fake()->randomElement(self::EMPLOYEE_REPLIES), false, $respondedAt->addMinutes(40)];
        }

        $this->createComments($ticket, collect($comments), $now);
    }

    /**
     * @param  Collection<int, array{0: User, 1: string, 2: bool, 3: CarbonImmutable}>  $comments
     */
    protected function createComments(Ticket $ticket, Collection $comments, CarbonImmutable $now): void
    {
        foreach ($comments as [$author, $body, $isInternal, $postedAt]) {
            $postedAt = $this->notAfter($postedAt, $now);

            TicketComment::factory()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => $isInternal,
                'created_at' => $postedAt,
                'updated_at' => $postedAt,
            ]);
        }
    }

    protected function notAfter(CarbonImmutable $time, CarbonImmutable $limit): CarbonImmutable
    {
        return $time->greaterThan($limit) ? $limit : $time;
    }
}
