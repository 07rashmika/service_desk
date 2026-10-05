<x-layouts.guest title="Sign in">
    <div class="flex flex-col gap-6 rounded-lg border border-slate-200 bg-white p-6 shadow-popover sm:p-8">
        <div class="text-center">
            <h1 class="text-xl font-semibold text-slate-900">Sign in to ServiceDesk</h1>
            <p class="mt-1 text-sm text-slate-500">IT support for company employees</p>
        </div>

        @if (session('status'))
            <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
        @endif

        @error('email')
            <x-ui.alert type="error" title="Sign-in failed">{{ $message }}</x-ui.alert>
        @enderror

        <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
            @csrf

            <x-ui.field label="Work email" for="email" error="">
                <x-ui.input name="email" type="email" icon="mail" :value="old('email')"
                    placeholder="name@company.com" required autofocus autocomplete="username" />
            </x-ui.field>

            <x-ui.field label="Password" for="password">
                <x-slot:corner>
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Forgot password?</a>
                </x-slot:corner>
                <x-ui.password-input name="password" required autocomplete="current-password" />
            </x-ui.field>

            <x-ui.checkbox name="remember" label="Remember me" />

            <x-ui.button type="submit" class="w-full" icon-trailing="arrow_forward">Sign in</x-ui.button>
        </form>
    </div>

    <p class="mt-6 flex items-center justify-center gap-1.5 text-label text-slate-500">
        <x-ui.icon name="admin_panel_settings" class="text-[16px]" />
        Accounts are created by your IT administrator.
    </p>

    @env('local')
        <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white/60 p-4 text-xs text-slate-600">
            <p class="mb-2 font-semibold text-slate-700">Demo accounts (local only) · password: <span class="font-mono">password</span></p>
            <ul class="flex flex-col gap-1 font-mono">
                <li>employee@servicedesk.test</li>
                <li>support@servicedesk.test</li>
                <li>admin@servicedesk.test</li>
            </ul>
        </div>
    @endenv
</x-layouts.guest>
