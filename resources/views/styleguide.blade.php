<x-layouts.app title="Style guide">
    <x-ui.page-header eyebrow="Design system" title="Style guide"
        description="Every ServiceDesk component with sample data. Compare against the Stitch designs before building a page.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download">Export CSV</x-ui.button>
            <x-ui.button icon="add">New Ticket</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Badges --}}
    <x-ui.card title="Badges" description="Status and priority colours come from App\Enums\Palette.">
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-20 text-xs font-medium text-slate-500">Status</span>
                @foreach ($statuses as $label => $color)
                    <x-ui.badge :color="$color">{{ $label }}</x-ui.badge>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-20 text-xs font-medium text-slate-500">Priority</span>
                @foreach ($priorities as $label => $color)
                    <x-ui.badge :color="$color" :dot="$label === 'High'" :pulse="$label === 'Critical'">{{ $label }}</x-ui.badge>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-20 text-xs font-medium text-slate-500">Other</span>
                <x-ui.ticket-ref>SD-000042</x-ui.ticket-ref>
                <x-ui.ticket-ref href="#">SD-000043</x-ui.ticket-ref>
                <x-ui.sla-timer :due="now()->addMinutes(18)" />
                <x-ui.sla-timer :due="now()->addMinutes(260)" />
                <x-ui.sla-timer :due="now()->subMinutes(42)" />
                <x-ui.sla-timer met />
                <x-ui.sla-timer paused />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-20 text-xs font-medium text-slate-500">Avatars</span>
                <x-ui.avatar name="Kasun Perera" size="xs" />
                <x-ui.avatar name="Sarah Jenkins" size="sm" />
                <x-ui.avatar name="Nimal Perera" />
                <x-ui.avatar name="Elena Rostova" size="lg" />
                <x-ui.logo class="ml-4" />
            </div>
        </div>
    </x-ui.card>

    {{-- Buttons --}}
    <x-ui.card title="Buttons">
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button icon="add">Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="danger" icon="cancel">Close tickets</x-ui.button>
                <x-ui.button variant="success" icon="check">Mark as resolved</x-ui.button>
                <x-ui.button icon-trailing="arrow_forward">Assign ticket</x-ui.button>
                <x-ui.button disabled>Disabled</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button size="sm">Take</x-ui.button>
                <x-ui.button size="sm" variant="secondary" icon="person_add">Claim</x-ui.button>
                <x-ui.button size="sm" variant="ghost" icon="refresh">Refresh</x-ui.button>
                <x-ui.button size="sm" variant="secondary" href="#">Link button</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Open tickets" value="3" icon="confirmation_number">+1 this week</x-ui.stat-card>
        <x-ui.stat-card label="In progress" value="2" unit="assigned" icon="autorenew" />
        <x-ui.stat-card label="Waiting for your reply" value="1" icon="notification_important" tone="warning" href="#">Direct input needed</x-ui.stat-card>
        <x-ui.stat-card label="Overdue SLA" value="2" icon="warning" tone="danger">Immediate action required</x-ui.stat-card>
    </div>

    {{-- Table --}}
    <div class="flex flex-col gap-4">
        <x-ui.tabs :items="[
            ['label' => 'Unassigned', 'href' => '#', 'count' => 8],
            ['label' => 'Assigned to me', 'href' => '#', 'count' => 12],
            ['label' => 'All open', 'href' => '#', 'count' => 43, 'active' => true],
            ['label' => 'Overdue', 'href' => '#', 'count' => 2, 'alert' => true],
            ['label' => 'Resolved', 'href' => '#', 'count' => 15],
        ]" />

        <x-ui.table>
            <x-slot:head>
                <x-ui.table.heading>Ref</x-ui.table.heading>
                <x-ui.table.heading>Title &amp; requester</x-ui.table.heading>
                <x-ui.table.heading>Category</x-ui.table.heading>
                <x-ui.table.heading>Priority</x-ui.table.heading>
                <x-ui.table.heading>Status</x-ui.table.heading>
                <x-ui.table.heading>Assigned to</x-ui.table.heading>
                <x-ui.table.heading>SLA</x-ui.table.heading>
            </x-slot:head>

            @foreach ($tickets as $ticket)
                <tr class="transition-colors hover:bg-slate-50">
                    <x-ui.table.cell><x-ui.ticket-ref href="#">{{ $ticket['ref'] }}</x-ui.ticket-ref></x-ui.table.cell>
                    <x-ui.table.cell class="min-w-64">
                        <p class="font-medium text-slate-900">{{ $ticket['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $ticket['requester'] }} · {{ $ticket['department'] }}</p>
                    </x-ui.table.cell>
                    <x-ui.table.cell class="whitespace-nowrap text-slate-600">{{ $ticket['category'] }}</x-ui.table.cell>
                    <x-ui.table.cell>
                        <x-ui.badge :color="$ticket['priority'][1]" :pulse="$ticket['priority'][0] === 'Critical'">{{ $ticket['priority'][0] }}</x-ui.badge>
                    </x-ui.table.cell>
                    <x-ui.table.cell><x-ui.badge :color="$ticket['status'][1]">{{ $ticket['status'][0] }}</x-ui.badge></x-ui.table.cell>
                    <x-ui.table.cell>
                        @if ($ticket['assignee'])
                            <span class="flex items-center gap-2 whitespace-nowrap">
                                <x-ui.avatar :name="$ticket['assignee']" size="sm" />
                                {{ $ticket['assignee'] }}
                            </span>
                        @else
                            <x-ui.button size="sm">Assign</x-ui.button>
                        @endif
                    </x-ui.table.cell>
                    <x-ui.table.cell>
                        <x-ui.sla-timer :due="$ticket['due']" :met="$ticket['met'] ?? false" :paused="$ticket['paused'] ?? false" />
                    </x-ui.table.cell>
                </tr>
            @endforeach

            <x-slot:footer>
                {{ $tickets->links() }}
            </x-slot:footer>
        </x-ui.table>
    </div>

    {{-- Forms --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="Form controls" class="xl:col-span-2">
            <form class="flex flex-col gap-5" onsubmit="return false">
                <x-ui.field label="Title / Summary" for="title" required corner="Short & descriptive"
                    error="Please provide a more descriptive summary (at least 10 characters).">
                    <x-ui.input name="title" value="Outlook" :invalid="true" />
                </x-ui.field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.field label="Issue category" for="category_id" required>
                        <x-ui.select name="category_id">
                            <option>Hardware</option>
                            <option>Software</option>
                            <option selected>Email &amp; Accounts</option>
                            <option>Network</option>
                        </x-ui.select>
                    </x-ui.field>
                    <x-ui.field label="Work email" for="email" hint="Accounts are created by your IT administrator.">
                        <x-ui.input name="email" type="email" icon="mail" placeholder="name@company.com" />
                    </x-ui.field>
                </div>

                <x-ui.field label="Urgency / Priority" required corner="Sets SLA benchmark">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <x-ui.radio-card name="priority" value="low" label="Low" color="slate" description="Minor inconvenience or general question." />
                        <x-ui.radio-card name="priority" value="medium" label="Medium" color="blue" description="Affects my work, but I have a workaround." />
                        <x-ui.radio-card name="priority" value="high" label="High" color="orange" description="I can't work; it's blocking my tasks." checked />
                        <x-ui.radio-card name="priority" value="critical" label="Critical" color="red" description="Affects many people or business stopped." />
                    </div>
                </x-ui.field>

                <x-ui.field label="Detailed description" for="description" required corner="Max 2000 characters">
                    <x-ui.textarea name="description" placeholder="Explain what happened, steps to reproduce, and what you tried." />
                </x-ui.field>

                <x-ui.field label="Attachments" hint="Screenshots help technicians diagnose issues faster.">
                    <x-ui.file-dropzone name="attachments[]" :max-files="3" />
                </x-ui.field>

                <div class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4">
                    <x-ui.checkbox name="notify" checked label="Notify employee by email" description="Send the resolution summary to the requester." />
                    <x-ui.toggle name="active" checked label="Active" description="Inactive users can't sign in." />
                    <x-ui.toggle name="digest" label="Weekly digest" />
                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 pt-5">
                    <x-ui.button variant="ghost">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon-trailing="send">Submit ticket</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <div class="flex flex-col gap-4">
            <x-ui.alert type="success" dismissible>Ticket SD-000042 was created.</x-ui.alert>
            <x-ui.alert type="error" title="Authentication failed">These credentials do not match our records.</x-ui.alert>
            <x-ui.alert type="warning">This ticket is approaching its SLA deadline.</x-ui.alert>
            <x-ui.alert>Sending a reply will move the ticket back to In Progress.</x-ui.alert>

            <x-ui.card :padding="false">
                <x-ui.empty-state icon="search_off" title="No tickets found"
                    description="Try adjusting your search or clearing the filters.">
                    <x-ui.button variant="secondary">Clear filters</x-ui.button>
                    <x-ui.button>Report an issue</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        </div>
    </div>

    {{-- Ticket timeline --}}
    <x-ui.card title="Ticket detail pieces" description="Workflow stepper, comments, internal notes and system events.">
        <div class="flex flex-col gap-4">
            <x-ticket.workflow-stepper color="orange" :current="3" :steps="[
                ['label' => 'Open', 'time' => '09:15 AM'],
                ['label' => 'Assigned', 'time' => '09:30 AM'],
                ['label' => 'In Progress', 'time' => '09:35 AM'],
                ['label' => 'Waiting for User'],
                ['label' => 'Resolved'],
                ['label' => 'Closed'],
            ]" />

            <x-ticket.timeline-comment author="Nimal Perera" role="Requester" time="2h ago" meta="via Web Portal">
                Every time I open Outlook it asks for my Microsoft 365 credentials, even after entering them.
                <x-slot:attachments>
                    <a href="#" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-label hover:bg-slate-100">
                        <x-ui.icon name="image" class="text-slate-500" /> outlook_prompt_error.png
                        <span class="font-mono text-xs text-slate-500">1.2 MB</span>
                    </a>
                </x-slot:attachments>
            </x-ticket.timeline-comment>

            <x-ticket.timeline-event icon="person_add" time="1h 45m ago">Assigned to Kasun Perera</x-ticket.timeline-event>
            <x-ticket.timeline-event icon="sync" color="amber" time="1h 40m ago">Status changed to In Progress</x-ticket.timeline-event>

            <x-ticket.timeline-comment author="Kasun Perera" role="IT Support" time="21m ago" internal>
                Verified the user's identity with their manager. Clearing the keychain entries should fix this.
            </x-ticket.timeline-comment>
        </div>
    </x-ui.card>

    {{-- Overlays --}}
    <x-ui.card title="Overlays">
        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="secondary" icon="person_add" x-data x-on:click="$dispatch('open-modal', 'assign-ticket')">Assign modal</x-ui.button>
            <x-ui.button variant="secondary" icon="check_circle" x-data x-on:click="$dispatch('open-modal', 'resolve-ticket')">Resolve modal</x-ui.button>
            <x-ui.button variant="secondary" icon="person" x-data x-on:click="$dispatch('open-modal', 'add-user')">User slide-over</x-ui.button>

            <x-ui.dropdown align="left">
                <x-slot:trigger>
                    <x-ui.button variant="secondary" icon-trailing="expand_more">Dropdown</x-ui.button>
                </x-slot:trigger>
                <x-ui.dropdown-link href="#" icon="edit">Edit</x-ui.dropdown-link>
                <x-ui.dropdown-link href="#" icon="lock_reset">Reset password</x-ui.dropdown-link>
                <x-ui.dropdown-link as="button" icon="block" danger>Deactivate</x-ui.dropdown-link>
            </x-ui.dropdown>
        </div>
    </x-ui.card>

    <x-ui.modal name="assign-ticket" title="Assign ticket" description="Select an available technician." icon="person_add">
        <div class="flex flex-col gap-4">
            <x-ui.input name="technician_search" icon="search" placeholder="Search technician by name…" />
            <div class="flex flex-col gap-2">
                @foreach (['Kasun Perera' => 5, 'Sarah Jenkins' => 12, 'Marcus Chen' => 3] as $name => $open)
                    <x-ui.radio-card name="assignee" :value="$name" :label="$name" :description="$open.' open tickets'" :checked="$loop->first" />
                @endforeach
            </div>
            <x-ui.field label="Handoff note" for="note" corner="Internal only">
                <x-ui.textarea name="note" rows="2" placeholder="Add context for the assignee…" />
            </x-ui.field>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'assign-ticket')">Cancel</x-ui.button>
            <x-ui.button icon-trailing="arrow_forward">Assign ticket</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal name="resolve-ticket" title="Resolve ticket" description="Document the fix for the requester." icon="task_alt">
        <x-ui.field label="Solution / Root cause" for="solution" required hint="This summary is visible to the requester.">
            <x-ui.textarea name="solution" placeholder="Describe what was done to fix the issue…" />
        </x-ui.field>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'resolve-ticket')">Cancel</x-ui.button>
            <x-ui.button variant="success" icon="check">Mark as resolved</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.slide-over name="add-user" title="Add new user" description="Create an account and choose a role.">
        <form id="add-user-form" class="flex flex-col gap-5" onsubmit="return false">
            <x-ui.field label="Full name" for="name" required>
                <x-ui.input name="name" />
            </x-ui.field>
            <x-ui.field label="Email" for="user_email" required>
                <x-ui.input name="user_email" type="email" />
            </x-ui.field>
            <x-ui.field label="Role" required>
                <div class="flex flex-col gap-2">
                    <x-ui.radio-card name="role" value="employee" label="Employee" description="Can create and track their own tickets." checked />
                    <x-ui.radio-card name="role" value="support" label="IT Support" description="Handles and resolves tickets." />
                    <x-ui.radio-card name="role" value="admin" label="Admin" description="Full system management." />
                </div>
            </x-ui.field>
            <x-ui.toggle name="user_active" checked label="Active" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'add-user')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="add-user-form">Save user</x-ui.button>
        </x-slot:footer>
    </x-ui.slide-over>
</x-layouts.app>
