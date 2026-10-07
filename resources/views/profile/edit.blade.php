<x-layouts.app title="My profile">
    <x-ui.page-header title="My profile" description="Your personal details and sign-in settings." />

    <div class="flex max-w-3xl flex-col gap-6">
        <x-ui.card title="Profile information" description="Your name and phone number are shown to IT staff on your tickets.">
            <form method="POST" action="{{ route('user-profile-information.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                @if (session('status') === 'profile-information-updated')
                    <x-ui.alert type="success" dismissible>Your profile has been updated.</x-ui.alert>
                @endif

                <div class="flex items-center gap-4">
                    <x-ui.avatar :name="$user->name" size="lg" />
                    <div>
                        <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="text-label text-slate-500">{{ $user->primaryRole()?->label() ?? 'No role assigned' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.field label="Full name" for="name" required :error="$errors->updateProfileInformation->first('name')">
                        <x-ui.input name="name" :value="old('name', $user->name)" required autocomplete="name"
                            :invalid="$errors->updateProfileInformation->has('name')" />
                    </x-ui.field>

                    <x-ui.field label="Phone" for="phone" :error="$errors->updateProfileInformation->first('phone')">
                        <x-ui.input name="phone" type="tel" icon="call" :value="old('phone', $user->phone)" autocomplete="tel"
                            :invalid="$errors->updateProfileInformation->has('phone')" />
                    </x-ui.field>

                    <x-ui.field label="Work email" for="email" hint="Managed by your IT administrator.">
                        <x-ui.input name="email" type="email" icon="mail" :value="$user->email" disabled />
                    </x-ui.field>

                    <x-ui.field label="Department" for="department">
                        <x-ui.input name="department" icon="apartment" :value="$user->department?->name ?? '—'" disabled />
                    </x-ui.field>

                    <x-ui.field label="Job title" for="job_title">
                        <x-ui.input name="job_title" icon="badge" :value="$user->job_title ?? '—'" disabled />
                    </x-ui.field>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <x-ui.button type="submit">Save changes</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Email notifications" description="You always see updates in the bell. Choose which ones are also emailed to you.">
            <form method="POST" action="{{ route('profile.notifications') }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                @if (session('status') === 'notifications-updated')
                    <x-ui.alert type="success" dismissible>Your email preferences have been saved.</x-ui.alert>
                @endif

                <div class="flex flex-col divide-y divide-slate-100">
                    @foreach ($preferences as $preference)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <input type="hidden" name="email[{{ $preference->value }}]" value="0">
                            <x-ui.toggle :name="'email['.$preference->value.']'" value="1" :checked="$user->wantsEmail($preference)"
                                :label="$preference->label()" :description="$preference->description()" />
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <x-ui.button type="submit">Save preferences</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Change password" description="Use a long password you don't use anywhere else.">
            <form method="POST" action="{{ route('user-password.update') }}" class="flex flex-col gap-5">
                @csrf
                @method('PUT')

                @if (session('status') === 'password-updated')
                    <x-ui.alert type="success" dismissible>Your password has been changed.</x-ui.alert>
                @endif

                <x-ui.field label="Current password" for="current_password" required :error="$errors->updatePassword->first('current_password')">
                    <x-ui.password-input name="current_password" required autocomplete="current-password"
                        :invalid="$errors->updatePassword->has('current_password')" />
                </x-ui.field>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.field label="New password" for="password" required hint="At least 8 characters."
                        :error="$errors->updatePassword->first('password')">
                        <x-ui.password-input name="password" required autocomplete="new-password"
                            :invalid="$errors->updatePassword->has('password')" />
                    </x-ui.field>

                    <x-ui.field label="Confirm new password" for="password_confirmation" required>
                        <x-ui.password-input name="password_confirmation" required autocomplete="new-password" />
                    </x-ui.field>
                </div>

                <div class="flex justify-end border-t border-slate-200 pt-5">
                    <x-ui.button type="submit">Update password</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
