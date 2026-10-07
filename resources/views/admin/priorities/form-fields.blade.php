{{-- Fields for the add and edit priority forms. Expects $priority (nullable) and $bag. --}}
@php($errorBag = $errors->getBag($bag))

<div class="flex flex-col gap-5">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <x-ui.field label="Name" :for="$bag.'-name'" required :error="$errorBag->first('name')" class="sm:col-span-2">
            <x-ui.input :id="$bag.'-name'" name="name" :value="old('name', $priority?->name)" required maxlength="50" :invalid="$errorBag->has('name')" />
        </x-ui.field>
        <x-ui.field label="Urgency level" :for="$bag.'-level'" required hint="1 = least urgent" :error="$errorBag->first('level')">
            <x-ui.input :id="$bag.'-level'" name="level" type="number" min="1" max="10" :value="old('level', $priority?->level ?? 1)" required :invalid="$errorBag->has('level')" />
        </x-ui.field>
    </div>
    <x-ui.field label="Description" :for="$bag.'-description'" hint="Helps employees pick the right priority." :error="$errorBag->first('description')">
        <x-ui.input :id="$bag.'-description'" name="description" :value="old('description', $priority?->description)" maxlength="255" :invalid="$errorBag->has('description')" />
    </x-ui.field>
    <x-ui.field label="Colour" required :error="$errorBag->first('color')">
        <x-ui.color-picker name="color" :selected="old('color', $priority?->color?->value ?? 'blue')" />
    </x-ui.field>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.field label="First response target" :for="$bag.'-response_hours'" required corner="hours" :error="$errorBag->first('response_hours')">
            <x-ui.input :id="$bag.'-response_hours'" name="response_hours" type="number" min="1" max="720" icon="timer"
                :value="old('response_hours', $priority?->response_hours ?? 4)" required :invalid="$errorBag->has('response_hours')" />
        </x-ui.field>
        <x-ui.field label="Resolution target" :for="$bag.'-resolution_hours'" required corner="hours" :error="$errorBag->first('resolution_hours')">
            <x-ui.input :id="$bag.'-resolution_hours'" name="resolution_hours" type="number" min="1" max="720" icon="task_alt"
                :value="old('resolution_hours', $priority?->resolution_hours ?? 24)" required :invalid="$errorBag->has('resolution_hours')" />
        </x-ui.field>
    </div>
    <x-ui.alert>New targets apply to tickets created from now on. Existing tickets keep their deadlines.</x-ui.alert>
</div>
