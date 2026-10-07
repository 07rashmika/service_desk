@php
    use App\Enums\TicketState;

    $state = $ticket->state();
    $user = auth()->user();
    $isMine = $ticket->assigned_to === $user->id;

    $responseDue = $ticket->created_at->copy()->addHours($ticket->priority->response_hours);
    $resolutionEnd = $ticket->resolved_at ?? now();
    $resolutionProgress = $ticket->due_at
        ? min(100, max(0, round($ticket->created_at->diffInSeconds($resolutionEnd) / max(1, $ticket->created_at->diffInSeconds($ticket->due_at)) * 100)))
        : 0;
@endphp

<x-layouts.app :title="$ticket->reference">
    <div class="flex flex-col gap-3">
        <nav class="flex items-center gap-1.5 text-label text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('support.tickets.index') }}" class="hover:text-slate-900">Ticket queue</a>
            <x-ui.icon name="chevron_right" class="text-[16px]" />
            <span class="font-mono text-slate-900">{{ $ticket->reference }}</span>
        </nav>

        <x-ui.page-header :title="$ticket->title">
            <x-slot:meta>
                <span class="flex w-full flex-wrap items-center gap-2 text-label text-slate-500 sm:order-first">
                    <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                    <x-ticket.status-badge :status="$ticket->status" />
                    <x-ticket.priority-badge :priority="$ticket->priority" />
                    <span>
                        Reported by {{ $ticket->creator->name }}{{ $ticket->loggedBy ? ' (logged by '.$ticket->loggedBy->name.')' : '' }}
                        · {{ $ticket->created_at->format('M j, g:i A') }}
                    </span>
                </span>
            </x-slot:meta>
        </x-ui.page-header>
    </div>

    {{-- What the technician can do next --}}
    <section class="flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white p-4">
        @can('take', $ticket)
            <p class="flex-1 text-label text-slate-600">This ticket is waiting for a technician.</p>
            @can('assign', $ticket)
                <x-ui.button variant="secondary" icon="person_add" x-on:click="$dispatch('open-modal', 'assign-ticket')">Assign…</x-ui.button>
            @endcan
            <form method="POST" action="{{ route('support.tickets.take', $ticket) }}">
                @csrf
                <x-ui.button type="submit" icon="front_hand">Take ticket</x-ui.button>
            </form>
        @elseif ($isMine && $ticket->isActive())
            <p class="flex flex-1 items-center gap-2 text-label text-slate-600">
                <x-ui.avatar :name="$user->name" size="sm" /> You're working on this ticket.
            </p>
            @can('changeStatus', [$ticket, TicketState::InProgress])
                <form method="POST" action="{{ route('support.tickets.start', $ticket) }}">
                    @csrf
                    <x-ui.button type="submit" :variant="$state === TicketState::Assigned ? 'primary' : 'secondary'" icon="play_arrow">
                        {{ $state === TicketState::Assigned ? 'Start progress' : 'Resume work' }}
                    </x-ui.button>
                </form>
            @endcan
            @can('changeStatus', [$ticket, TicketState::WaitingForUser])
                <x-ui.button variant="secondary" icon="contact_support" x-on:click="$dispatch('open-modal', 'ask-requester')">Ask requester</x-ui.button>
            @endcan
            @can('resolve', $ticket)
                <x-ui.button variant="success" icon="task_alt" x-on:click="$dispatch('open-modal', 'resolve-ticket')">Resolve</x-ui.button>
            @endcan
            @can('assign', $ticket)
                <x-ui.button variant="ghost" icon="swap_horiz" x-on:click="$dispatch('open-modal', 'assign-ticket')">Reassign</x-ui.button>
            @endcan
        @elseif ($ticket->assignee && $ticket->isActive())
            <p class="flex flex-1 items-center gap-2 text-label text-slate-600">
                <x-ui.avatar :name="$ticket->assignee->name" size="sm" />
                {{ $ticket->assignee->name }} is working on this ticket.
            </p>
            @can('assign', $ticket)
                <x-ui.button variant="secondary" icon="swap_horiz" x-on:click="$dispatch('open-modal', 'assign-ticket')">Reassign</x-ui.button>
            @endcan
        @elseif ($state === TicketState::Resolved)
            <p class="flex flex-1 items-center gap-2 text-label text-slate-600">
                <x-ui.icon name="hourglass_top" class="text-[18px] text-emerald-600" />
                Resolved {{ $ticket->resolved_at?->diffForHumans() }}. Waiting for {{ $ticket->creator->name }} to confirm the fix.
            </p>
        @else
            <p class="flex flex-1 items-center gap-2 text-label text-slate-600">
                <x-ui.icon name="lock" class="text-[18px]" />
                Closed {{ $ticket->closed_at?->diffForHumans() }}.
            </p>
        @endcan
    </section>

    <x-ui.card title="Workflow status">
        <x-ticket.workflow-stepper :steps="$steps['steps']" :current="$steps['current']" :color="$steps['color']" />
    </x-ui.card>

    @if ($ticket->solution && ! $ticket->isActive())
        <x-ui.alert type="success" title="Solution">
            <span class="whitespace-pre-line">{{ $ticket->solution }}</span>
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-4 lg:col-span-2">
            <x-ticket.activity :ticket="$ticket" :timeline="$timeline" />

            @if ($user->can('comment', $ticket) || $user->can('addInternalNote', $ticket))
                <section id="reply" class="scroll-mt-20 rounded-lg border border-slate-200 bg-white"
                    x-data="{ internal: @js((bool) old('internal', ! $user->can('comment', $ticket))) }">
                    <div class="flex border-b border-slate-200 px-2" role="tablist">
                        @can('comment', $ticket)
                            <button type="button" role="tab" x-on:click="internal = false" x-bind:aria-selected="(! internal).toString()"
                                class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-3 py-3 text-label font-medium"
                                x-bind:class="internal ? 'border-transparent text-slate-500 hover:text-slate-900' : 'border-primary-600 text-primary-700'">
                                <x-ui.icon name="reply" class="text-[18px]" /> Reply to {{ str($ticket->creator->name)->before(' ') }}
                            </button>
                        @endcan
                        @can('addInternalNote', $ticket)
                            <button type="button" role="tab" x-on:click="internal = true" x-bind:aria-selected="internal.toString()"
                                class="-mb-px inline-flex items-center gap-1.5 border-b-2 px-3 py-3 text-label font-medium"
                                x-bind:class="internal ? 'border-primary-600 text-primary-700' : 'border-transparent text-slate-500 hover:text-slate-900'">
                                <x-ui.icon name="lock" class="text-[18px]" /> Internal note
                            </button>
                        @endcan
                    </div>

                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data"
                        class="flex flex-col gap-3 p-5" x-bind:class="internal && 'bg-primary-50/40'">
                        @csrf
                        <input type="hidden" name="internal" x-bind:value="internal ? 1 : 0">

                        <p x-cloak x-show="internal" class="flex items-center gap-1.5 text-xs font-medium text-primary-700">
                            <x-ui.icon name="visibility_off" class="text-[16px]" /> Only IT staff can see internal notes.
                        </p>

                        <x-ui.field for="body" :error="$errors->first('body') ?: ($errors->first('attachments') ?: $errors->first('attachments.*'))">
                            <label for="body" class="sr-only">Message</label>
                            <x-ui.textarea name="body" rows="4" maxlength="5000" required
                                x-bind:placeholder="internal ? 'Add a note for the IT team…' : {{ \Illuminate\Support\Js::from('Type your reply to '.$ticket->creator->name.'…') }}">{{ old('body') }}</x-ui.textarea>
                        </x-ui.field>

                        <x-ui.file-dropzone name="attachments[]" compact
                            :max-files="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENTS"
                            :max-size-mb="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENT_KB / 1024" />

                        <div class="flex justify-end border-t border-slate-200 pt-3">
                            <x-ui.button type="submit" icon-trailing="send">
                                <span x-text="internal ? 'Add internal note' : 'Send reply'">Send reply</span>
                            </x-ui.button>
                        </div>
                    </form>
                </section>
            @endif
        </div>

        <aside class="flex flex-col gap-4">
            <x-ui.card title="Requester">
                <div class="flex items-center gap-3">
                    <x-ui.avatar :name="$ticket->creator->name" size="lg" />
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $ticket->creator->name }}</p>
                        <p class="truncate text-label text-slate-500">{{ $ticket->creator->job_title ?? 'Employee' }}</p>
                    </div>
                </div>
                <dl class="mt-4 flex flex-col gap-2.5 rounded-lg bg-slate-50 p-3 text-label">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="min-w-0 truncate font-mono text-xs text-slate-900">{{ $ticket->creator->email }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Phone</dt>
                        <dd class="text-slate-900">{{ $ticket->creator->phone ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Department</dt>
                        <dd class="text-right text-slate-900">{{ $ticket->creator->department?->name ?? '—' }}</dd>
                    </div>
                    @if ($ticket->loggedBy)
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Logged by</dt>
                            <dd class="text-right text-slate-900">{{ $ticket->loggedBy->name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tickets reported</dt>
                        <dd class="text-slate-900">{{ $requesterTicketCount }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="SLA">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-label font-medium text-slate-900">First response</p>
                            <p class="text-xs text-slate-500">Target {{ $ticket->priority->response_hours }}h · by {{ $responseDue->format('M j, g:i A') }}</p>
                        </div>
                        @if ($ticket->first_response_at)
                            <x-ui.sla-timer :met="$ticket->first_response_at->lte($responseDue)" :missed="$ticket->first_response_at->gt($responseDue)" />
                        @elseif ($ticket->isActive())
                            <x-ui.sla-timer :due="$responseDue" />
                        @else
                            <x-ui.sla-timer />
                        @endif
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-label font-medium text-slate-900">Resolution</p>
                                <p class="text-xs text-slate-500">Target {{ $ticket->priority->resolution_hours }}h · by {{ $ticket->due_at?->format('M j, g:i A') ?? '—' }}</p>
                            </div>
                            <x-ticket.sla :ticket="$ticket" />
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Time used" aria-valuenow="{{ $resolutionProgress }}" aria-valuemin="0" aria-valuemax="100">
                            <div @class([
                                'h-full rounded-full',
                                'bg-red-500' => $resolutionProgress >= 100,
                                'bg-amber-500' => $resolutionProgress >= 75 && $resolutionProgress < 100,
                                'bg-primary-600' => $resolutionProgress < 75,
                            ]) style="width: {{ $resolutionProgress }}%"></div>
                        </div>
                        <p class="text-xs text-slate-500">{{ $resolutionProgress }}% of the resolution time used</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Classification">
                @can('triage', $ticket)
                    <form method="POST" action="{{ route('support.tickets.triage', $ticket) }}" class="flex flex-col gap-4">
                        @csrf
                        @method('PATCH')
                        <x-ui.field label="Category" for="category_id" :error="$errors->triage->first('category_id')">
                            <x-ui.select name="category_id">
                                @foreach ($categories as $category)
                                    @continue(! $category->is_active && $category->id !== $ticket->category_id)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $ticket->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.field label="Priority" for="priority_id" hint="Changing the priority moves the SLA deadline." :error="$errors->triage->first('priority_id')">
                            <x-ui.select name="priority_id">
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->id }}" @selected(old('priority_id', $ticket->priority_id) == $priority->id)>{{ $priority->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <x-ui.button type="submit" variant="secondary" class="w-full">Save changes</x-ui.button>
                    </form>
                @else
                    <dl class="flex flex-col gap-3 text-label">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Category</dt><dd class="font-medium text-slate-900">{{ $ticket->category->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Priority</dt><dd><x-ticket.priority-badge :priority="$ticket->priority" /></dd></div>
                    </dl>
                @endcan
            </x-ui.card>

            <x-ui.card title="Assignment history">
                @forelse ($ticket->assignments->reverse() as $assignment)
                    <div class="flex gap-3 border-l-2 border-slate-200 py-1 pl-3 text-label first:border-primary-500">
                        <div>
                            <p class="text-slate-900">
                                {{ $assignment->assigned_by === $assignment->assigned_to ? $assignment->assignee->name.' took the ticket' : 'Assigned to '.$assignment->assignee->name }}
                            </p>
                            <p class="text-xs text-slate-500">
                                @if ($assignment->assigner && $assignment->assigned_by !== $assignment->assigned_to)
                                    by {{ $assignment->assigner->name }} ·
                                @endif
                                {{ $assignment->created_at->diffForHumans() }}
                            </p>
                            @if ($assignment->note)
                                <p class="mt-1 text-xs text-slate-600 italic">“{{ $assignment->note }}”</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-label text-slate-500">Not assigned yet.</p>
                @endforelse
            </x-ui.card>
        </aside>
    </div>

    @can('assign', $ticket)
        <x-ui.modal name="assign-ticket" :title="$ticket->assignee ? 'Reassign ticket' : 'Assign ticket'" icon="person_add"
            :show="$errors->assignTicket->isNotEmpty()" :description="$ticket->reference.' · choose who should work on it'">
            <form id="assign-form" method="POST" action="{{ route('support.tickets.assign', $ticket) }}" x-data="{ search: '' }" class="flex flex-col gap-4">
                @csrf
                <x-ui.input name="technician_search" type="search" icon="search" placeholder="Search technicians…"
                    x-model="search" autocomplete="off" />

                <div class="flex max-h-72 flex-col gap-2 overflow-y-auto pr-1" role="radiogroup" aria-label="Technicians">
                    @foreach ($technicians as $technician)
                        @php
                            $isCurrent = $technician->id === $ticket->assigned_to;
                            $load = $technician->active_tickets_count;
                            $loadColor = $load >= 10 ? 'red' : ($load >= 5 ? 'amber' : 'emerald');
                        @endphp
                        <label x-show="@js(mb_strtolower($technician->name)).includes(search.toLowerCase())"
                            @class([
                                'flex items-center gap-3 rounded-lg border p-3 transition-colors has-checked:border-primary-600 has-checked:bg-primary-50/50 has-checked:ring-1 has-checked:ring-primary-600',
                                'cursor-not-allowed border-slate-100 opacity-60' => $isCurrent,
                                'cursor-pointer border-slate-200 hover:border-slate-300' => ! $isCurrent,
                            ])>
                            <input type="radio" name="technician_id" value="{{ $technician->id }}" class="sr-only"
                                @disabled($isCurrent) @checked(old('technician_id') == $technician->id)>
                            <x-ui.avatar :name="$technician->name" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-label font-medium text-slate-900">
                                    {{ $technician->name }}{{ $technician->id === auth()->id() ? ' (you)' : '' }}
                                </span>
                                <span class="block truncate text-xs text-slate-500">{{ $isCurrent ? 'Currently assigned' : ($technician->job_title ?? 'IT Support') }}</span>
                            </span>
                            <x-ui.badge :color="$loadColor" dot>{{ $load }} open</x-ui.badge>
                        </label>
                    @endforeach
                </div>
                @if ($errors->assignTicket->has('technician_id'))
                    <p class="text-xs text-red-600">{{ $errors->assignTicket->first('technician_id') }}</p>
                @endif

                <x-ui.field label="Handoff note" for="note" corner="Internal only" :error="$errors->assignTicket->first('note')">
                    <x-ui.textarea name="note" rows="2" maxlength="1000" placeholder="Context for the technician, e.g. what you've already tried…">{{ old('note') }}</x-ui.textarea>
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'assign-ticket')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="assign-form" icon-trailing="arrow_forward">Assign ticket</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endcan

    @can('changeStatus', [$ticket, TicketState::WaitingForUser])
        <x-ui.modal name="ask-requester" title="Ask the requester" icon="contact_support" :show="$errors->askRequester->isNotEmpty()"
            :description="'Your question is sent to '.$ticket->creator->name.'. The ticket waits for their reply and leaves the overdue list until then.'">
            <form id="ask-form" method="POST" action="{{ route('support.tickets.ask', $ticket) }}">
                @csrf
                <x-ui.field label="What do you need from them?" for="question" required :error="$errors->askRequester->first('question')">
                    <x-ui.textarea name="question" rows="4" required :invalid="$errors->askRequester->has('question')"
                        placeholder="Could you send a screenshot of the error message?">{{ old('question') }}</x-ui.textarea>
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'ask-requester')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="ask-form" icon-trailing="send">Send &amp; wait for reply</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endcan

    @can('resolve', $ticket)
        <x-ui.modal name="resolve-ticket" title="Resolve ticket" icon="task_alt" :show="$errors->resolveTicket->isNotEmpty()"
            :description="$ticket->reference.' · '.$ticket->title">
            <form id="resolve-form" method="POST" action="{{ route('support.tickets.resolve', $ticket) }}">
                @csrf
                <x-ui.field label="Solution / root cause" for="solution" required
                    :error="$errors->resolveTicket->first('solution')"
                    :hint="$ticket->creator->name.' sees this and is asked to confirm the fix or reopen the ticket.'">
                    <x-ui.textarea name="solution" rows="5" required :invalid="$errors->resolveTicket->has('solution')"
                        placeholder="Describe what was wrong and what you did to fix it…">{{ old('solution') }}</x-ui.textarea>
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'resolve-ticket')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="resolve-form" variant="success" icon="check">Mark as resolved</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endcan
</x-layouts.app>
