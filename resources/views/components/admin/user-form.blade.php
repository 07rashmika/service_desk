@props(['user' => null, 'bag', 'departments', 'roles', 'formId'])

@php
    $errorBag = $errors->getBag($bag);
    $currentRole = old('role', $user?->primaryRole()?->value ?? \App\Enums\RoleName::Employee->value);
@endphp

{{-- Fields for adding or editing a user. Errors come from the given bag. --}}
<form id="{{ $formId }}" method="POST" class="flex flex-col gap-5"
    action="{{ $user ? route('admin.users.update', [$user, ...request()->only(['search', 'role', 'department', 'status', 'page'])]) : route('admin.users.store') }}">
    @csrf
    @if ($user)
        @method('PUT')
    @endif

    <x-ui.field label="Full name" :for="$formId.'-name'" required :error="$errorBag->first('name')">
        <x-ui.input :id="$formId.'-name'" name="name" :value="old('name', $user?->name)" required :invalid="$errorBag->has('name')" />
    </x-ui.field>

    <x-ui.field label="Work email" :for="$formId.'-email'" required :error="$errorBag->first('email')"
        :hint="$user ? null : 'We email this address a link to set their password.'">
        <x-ui.input :id="$formId.'-email'" name="email" type="email" icon="mail" :value="old('email', $user?->email)" required :invalid="$errorBag->has('email')" />
    </x-ui.field>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.field label="Job title" :for="$formId.'-job_title'" :error="$errorBag->first('job_title')">
            <x-ui.input :id="$formId.'-job_title'" name="job_title" :value="old('job_title', $user?->job_title)" :invalid="$errorBag->has('job_title')" />
        </x-ui.field>
        <x-ui.field label="Phone" :for="$formId.'-phone'" :error="$errorBag->first('phone')">
            <x-ui.input :id="$formId.'-phone'" name="phone" type="tel" :value="old('phone', $user?->phone)" :invalid="$errorBag->has('phone')" />
        </x-ui.field>
    </div>

    <x-ui.field label="Department" :for="$formId.'-department_id'" :error="$errorBag->first('department_id')">
        <x-ui.select :id="$formId.'-department_id'" name="department_id">
            <option value="">No department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $user?->department_id) == $department->id)>{{ $department->name }}</option>
            @endforeach
        </x-ui.select>
    </x-ui.field>

    <x-ui.field label="Role" required :error="$errorBag->first('role')">
        <div class="flex flex-col gap-2">
            @foreach ($roles as $role)
                <x-ui.radio-card name="role" :value="$role->value" :label="$role->label()" :description="$role->description()"
                    :checked="$currentRole === $role->value" />
            @endforeach
        </div>
    </x-ui.field>
</form>
