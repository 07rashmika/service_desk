<x-layouts.guest title="Forgot password">
    <div class="flex flex-col gap-6 rounded-lg border border-slate-200 bg-white p-6 shadow-popover sm:p-8">
        <div class="text-center">
            <span class="mx-auto mb-3 flex size-11 items-center justify-center rounded-full bg-primary-50 text-primary-600">
                <x-ui.icon name="lock_reset" />
            </span>
            <h1 class="text-xl font-semibold text-slate-900">Forgot your password?</h1>
            <p class="mt-1 text-sm text-slate-500">Enter your work email and we'll send you a link to choose a new one.</p>
        </div>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <x-ui.field label="Work email" for="email">
                <x-ui.input name="email" type="email" icon="mail" :value="old('email')"
                    placeholder="name@company.com" required autofocus autocomplete="username" />
            </x-ui.field>

            <x-ui.button type="submit" class="w-full">Send reset link</x-ui.button>
        </form>
    </div>

    <p class="mt-6 text-center text-label">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 font-medium text-primary-600 hover:text-primary-700">
            <x-ui.icon name="arrow_back" class="text-[16px]" /> Back to sign in
        </a>
    </p>
</x-layouts.guest>
