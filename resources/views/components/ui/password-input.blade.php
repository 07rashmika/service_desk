@props(['invalid' => null])

<div class="relative" x-data="{ show: false }">
    <x-ui.input type="password" icon="lock" :invalid="$invalid" :error-icon="false"
        x-bind:type="show ? 'text' : 'password'"
        {{ $attributes->class('pr-10') }} />

    <button type="button" class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600"
        x-on:click="show = ! show" x-bind:aria-pressed="show.toString()">
        <span class="sr-only" x-text="show ? 'Hide password' : 'Show password'">Show password</span>
        <x-ui.icon name="visibility" class="text-[18px]" x-show="! show" />
        <x-ui.icon name="visibility_off" class="text-[18px]" x-cloak x-show="show" />
    </button>
</div>
