@php
    $greeting = match (true) {
        now()->hour < 12 => 'Good morning',
        now()->hour < 18 => 'Good afternoon',
        default => 'Good evening',
    };
@endphp

<x-layouts.app title="Dashboard">
    <x-ui.page-header :eyebrow="$user->primaryRole()?->label()"
        :title="$greeting.', '.str($user->name)->before(' ')"
        :description="collect([$user->job_title, $user->department?->name])->filter()->implode(' · ')" />

    <x-ui.card :padding="false">
        <x-ui.empty-state icon="construction" title="Your dashboard is on its way"
            description="Ticket pages for your role are being built in the next phase. You can already update your profile and password.">
            <x-ui.button variant="secondary" icon="account_circle" :href="route('profile.edit')">Go to profile</x-ui.button>
        </x-ui.empty-state>
    </x-ui.card>
</x-layouts.app>
