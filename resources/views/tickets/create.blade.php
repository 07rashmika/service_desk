<x-layouts.app title="Report an issue">
    <div class="flex flex-col gap-2">
        <nav class="flex items-center gap-1.5 text-label text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('dashboard') }}" class="hover:text-slate-900">Dashboard</a>
            <x-ui.icon name="chevron_right" class="text-[16px]" />
            <span class="text-slate-900">Report an issue</span>
        </nav>
        <x-ui.page-header title="Report an IT issue"
            description="Tell the IT support team what's wrong. You'll be able to follow progress and reply on the ticket page." />
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
                @csrf

                @if ($requesters)
                    <div class="flex flex-col gap-1.5 rounded-lg border border-primary-200 bg-primary-50/50 p-4">
                        <x-ui.field label="Who is this ticket for?" for="requester_id"
                            hint="Logging a phone call or walk-in? Choose the employee. They'll see the ticket in their My Tickets and can reply to it.">
                            <x-ui.select name="requester_id">
                                <option value="">Myself ({{ auth()->user()->name }})</option>
                                @foreach ($requesters as $requester)
                                    <option value="{{ $requester->id }}" @selected(old('requester_id') == $requester->id)>
                                        {{ $requester->name }}{{ $requester->department ? ' — '.$requester->department->name : '' }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                    </div>
                @endif

                <x-ui.field label="Title / Summary" for="title" required corner="Short & descriptive">
                    <x-ui.input name="title" :value="old('title')" maxlength="200" required autofocus
                        placeholder="e.g. Laptop won't connect to the office Wi-Fi" />
                </x-ui.field>

                <x-ui.field label="Category" for="category_id" required>
                    <x-ui.select name="category_id" required>
                        <option value="" disabled @selected(! old('category_id'))>Choose what the issue is about…</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Urgency / Priority" for="priority_id" required corner="Sets how quickly we respond">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($priorities as $priority)
                            <x-ui.radio-card name="priority_id" :value="$priority->id" :label="$priority->name"
                                :color="$priority->color" :description="$priority->description"
                                :checked="old('priority_id', $defaultPriorityId) == $priority->id" />
                        @endforeach
                    </div>
                </x-ui.field>

                <x-ui.field label="Detailed description" for="description" required
                    hint="What happened, when it started, any error messages, and what you've already tried.">
                    <div x-data="{ length: {{ mb_strlen((string) old('description')) }} }">
                        <x-ui.textarea name="description" rows="6" maxlength="5000" required
                            x-on:input="length = $event.target.value.length"
                            placeholder="Every time I open Outlook it asks for my password again…">{{ old('description') }}</x-ui.textarea>
                        <p class="mt-1 text-right font-mono text-xs text-slate-400"><span x-text="length"></span> / 5000</p>
                    </div>
                </x-ui.field>

                <x-ui.field label="Attachments" :error="$errors->first('attachments') ?: $errors->first('attachments.*')">
                    <x-ui.file-dropzone name="attachments[]"
                        :max-files="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENTS"
                        :max-size-mb="\App\Http\Requests\StoreTicketRequest::MAX_ATTACHMENT_KB / 1024"
                        hint="PNG, JPG, GIF, PDF, TXT or LOG · up to 5 files, 5 MB each" />
                </x-ui.field>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-5">
                    <x-ui.button variant="ghost" :href="route('tickets.index')">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon-trailing="send">Submit ticket</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Tips for faster help">
            <ul class="flex flex-col gap-4 text-label">
                @foreach ([
                    ['check_circle', 'Be specific', 'Include exact error messages and what you were doing.'],
                    ['schedule', 'Say when it started', 'Did it follow an update, a restart or a password change?'],
                    ['image', 'Attach screenshots', 'A picture of the error often halves the time to fix it.'],
                    ['priority_high', 'Pick the right priority', 'Critical is for outages that stop many people from working.'],
                ] as [$icon, $title, $text])
                    <li class="flex gap-3">
                        <x-ui.icon :name="$icon" class="text-[18px] text-primary-600" />
                        <span>
                            <span class="block font-medium text-slate-900">{{ $title }}</span>
                            <span class="text-slate-500">{{ $text }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
</x-layouts.app>
