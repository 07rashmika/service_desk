@props(['ticket', 'timeline'])

{{-- The original request followed by comments and events in date order. Shared by the requester's and the support view. --}}
<div class="flex flex-col gap-4">
    <x-ticket.timeline-comment :author="$ticket->creator->name" role="Requester"
        :time="$ticket->created_at->diffForHumans()"
        :meta="$ticket->loggedBy ? 'Logged by '.$ticket->loggedBy->name.' on their behalf' : 'Original request'">
        {{ $ticket->description }}

        @if ($ticket->attachments->isNotEmpty())
            <x-slot:attachments>
                @foreach ($ticket->attachments as $attachment)
                    <x-ticket.attachment-chip :attachment="$attachment" />
                @endforeach
            </x-slot:attachments>
        @endif
    </x-ticket.timeline-comment>

    @foreach ($timeline as $item)
        @if ($item['type'] === 'event')
            <x-ticket.timeline-event :icon="$item['icon']" :color="$item['color']" :time="$item['at']->diffForHumans()">
                {{ $item['text'] }}
            </x-ticket.timeline-event>
        @else
            @php($comment = $item['comment'])
            <x-ticket.timeline-comment id="comment-{{ $comment->id }}" class="scroll-mt-20"
                :author="$comment->user->name"
                :role="$comment->user_id === $ticket->created_by ? 'Requester' : ($comment->user->primaryRole()?->label() ?? 'Staff')"
                :time="$comment->created_at->diffForHumans()" :internal="$comment->is_internal">
                {{ $comment->body }}

                @if ($comment->attachments->isNotEmpty())
                    <x-slot:attachments>
                        @foreach ($comment->attachments as $attachment)
                            <x-ticket.attachment-chip :attachment="$attachment" />
                        @endforeach
                    </x-slot:attachments>
                @endif
            </x-ticket.timeline-comment>
        @endif
    @endforeach
</div>
