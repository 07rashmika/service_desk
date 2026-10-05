<x-layouts.guest title="Reset password">
    <div class="flex flex-col gap-6 rounded-lg border border-slate-200 bg-white p-6 shadow-popover sm:p-8">
        <div class="text-center">
            <span class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                <x-ui.icon name="password" />
            </span>
            <h1 class="text-xl font-semibold text-slate-900">Choose a new password</h1>
            <p class="mt-1 text-sm text-slate-500">Use at least 8 characters.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <x-ui.field label="Work email" for="email">
                <x-ui.input name="email" type="email" icon="mail" :value="old('email', $request->email)" required autocomplete="username" />
            </x-ui.field>

            <x-ui.field label="New password" for="password">
                <x-ui.password-input name="password" required autofocus autocomplete="new-password" />
            </x-ui.field>

            <x-ui.field label="Confirm new password" for="password_confirmation">
                <x-ui.password-input name="password_confirmation" required autocomplete="new-password" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Reset password</x-ui.button>
        </form>
    </div>
</x-layouts.guest>
