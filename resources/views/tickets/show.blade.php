@php
    use App\Enums\TicketState;

    $state = $ticket->state();
@endphp

<x-layouts.app :title="$ticket->reference">
    <div class="flex flex-col gap-3">
        <a href="{{ route('tickets.index') }}" class="inline-flex w-fit items-center gap-1 text-label text-slate-500 hover:text-slate-900">
            <x-ui.icon name="arrow_back" class="text-[18px]" /> Back to my tickets
        </a>

        <x-ui.page-header :title="$ticket->title">
            <x-slot:meta>
                <span class="flex w-full flex-wrap items-center gap-2 text-label text-slate-500 sm:order-first">
                    <x-ui.ticket-ref>{{ $ticket->reference }}</x-ui.ticket-ref>
                    <x-ticket.status-badge :status="$ticket->status" />
                    <x-ticket.priority-badge :priority="$ticket->priority" />
                    <span>Reported {{ $ticket->created_at->format('M j, Y \a\t g:i A') }}</span>
                </span>
            </x-slot:meta>
        </x-ui.page-header>
    </div>

    <x-ui.card title="Workflow status">
        @if ($state === TicketState::WaitingForUser && $ticket->created_by === auth()->id())
            <x-slot:actions>
                <x-ui.badge color="orange" dot>Your reply is needed</x-ui.badge>
            </x-slot:actions>
        @endif
        <x-ticket.workflow-stepper :steps="$steps['steps']" :current="$steps['current']" :color="$steps['color']" />
    </x-ui.card>

    @if ($state === TicketState::Resolved && auth()->user()->can('close', $ticket))
        <section class="flex flex-col gap-4 rounded-lg border border-emerald-200 bg-emerald-50 p-5 lg:flex-row lg:items-center">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                <x-ui.icon name="task_alt" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-emerald-900">{{ $ticket->assignee?->name ?? 'IT Support' }} marked this ticket as resolved</h2>
                @if ($ticket->solution)
                    <p class="mt-1 text-sm whitespace-pre-line text-emerald-800">{{ $ticket->solution }}</p>
                @endif
                <p class="mt-1 text-xs text-emerald-700">Is everything working again?</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                    @csrf
                    <x-ui.button type="submit" variant="success" icon="done_all">Confirm &amp; close ticket</x-ui.button>
                </form>
                <x-ui.button variant="secondary" icon="replay" x-data x-on:click="$dispatch('open-modal', 'reopen-ticket')">Not fixed – reopen</x-ui.button>
            </div>
        </section>

        <x-ui.modal name="reopen-ticket" title="Reopen this ticket" icon="replay" :show="$errors->has('reason')"
            description="Tell the technician what's still not working.">
            <form id="reopen-form" method="POST" action="{{ route('tickets.reopen', $ticket) }}">
                @csrf
                <x-ui.field label="What's still wrong?" for="reason" required>
                    <x-ui.textarea name="reason" rows="4" required placeholder="It worked for a while, but this morning…">{{ old('reason') }}</x-ui.textarea>
                </x-ui.field>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'reopen-ticket')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="reopen-form" icon="replay">Reopen ticket</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @elseif ($state === TicketState::Closed)
        <x-ui.alert type="info" title="This ticket is closed">
            @if ($ticket->solution)
                <span class="whitespace-pre-line">Solution: {{ $ticket->solution }}</span><br>
            @endif
            If the problem comes back, please <a href="{{ route('tickets.create') }}" class="font-medium underline">report a new issue</a>.
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <div class="flex min-w-0 flex-col gap-4 lg:col-span-2">
            <x-ticket.activity :ticket="$ticket" :timeline="$timeline" />

            @can('comment', $ticket)
                <x-ui.card id="reply" class="scroll-mt-20" :title="$ticket->assignee ? 'Reply to '.$ticket->assignee->name : 'Add a reply'">
                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                        @csrf
                        <x-ui.field for="body" :error="$errors->first('body') ?: ($errors->first('attachments') ?: $errors->first('attachments.*'))">
                            <label for="body" class="sr-only">Your reply</label>
                            <x-ui.textarea name="body" rows="4" maxlength="5000" required
                                placeholder="Type your reply or update here…">{{ old('body') }}</x-ui.textarea>
                        </x-ui.field>

                        <x-ui.file-dropzone name="attachments[]" compact
                            :max-files="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENTS"
                            :max-size-mb="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENT_KB / 1024" />

                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3">
                            <p class="flex items-center gap-1.5 text-xs text-slate-500">
                                @if ($state === TicketState::WaitingForUser && $ticket->created_by === auth()->id())
                                    <x-ui.icon name="info" class="text-[16px]" />
                                    Sending a reply moves the ticket back to In Progress.
                                @endif
                            </p>
                            <x-ui.button type="submit" icon-trailing="send">Send reply</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endcan
        </div>

        <aside class="flex flex-col gap-4">
            <x-ui.card title="Ticket information">
                <dl class="flex flex-col gap-3 text-label">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Category</dt>
                        <dd class="font-medium text-slate-900">{{ $ticket->category->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Priority</dt>
                        <dd><x-ticket.priority-badge :priority="$ticket->priority" /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Requester</dt>
                        <dd class="text-right font-medium text-slate-900">
                            {{ $ticket->creator->name }}
                            @if ($ticket->creator->department)
                                <span class="block text-xs font-normal text-slate-500">{{ $ticket->creator->department->name }}</span>
                            @endif
                        </dd>
                    </div>
                    @if ($ticket->loggedBy)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Logged by</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $ticket->loggedBy->name }} <span class="block text-xs font-normal text-slate-500">on your behalf</span></dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Assigned to</dt>
                        <dd>
                            @if ($ticket->assignee)
                                <span class="flex items-center gap-2 font-medium text-slate-900"><x-ui.avatar :name="$ticket->assignee->name" size="sm" /> {{ $ticket->assignee->name }}</span>
                            @else
                                <span class="text-slate-400 italic">Not assigned yet</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Created</dt>
                        <dd class="font-mono text-xs text-slate-700">{{ $ticket->created_at->format('M j, Y · g:i A') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Last updated</dt>
                        <dd class="text-slate-700">{{ $ticket->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Resolution target">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-label text-slate-500">
                            @if ($state === TicketState::WaitingForUser)
                                Paused while we wait for your reply
                            @elseif ($ticket->isActive())
                                Due by
                            @else
                                Was due by
                            @endif
                        </p>
                        <p class="font-medium text-slate-900">{{ $ticket->due_at?->format('M j, g:i A') ?? '—' }}</p>
                    </div>
                    <x-ticket.sla :ticket="$ticket" />
                </div>
                <p class="mt-3 text-xs text-slate-500">
                    {{ $ticket->priority->name }} priority tickets are resolved within {{ $ticket->priority->resolution_hours }} hours.
                </p>
            </x-ui.card>
        </aside>
    </div>
</x-layouts.app>
