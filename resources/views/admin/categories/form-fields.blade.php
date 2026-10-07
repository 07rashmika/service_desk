{{-- Fields for the add and edit category forms. Expects $category (nullable), $bag and $icons. --}}
@php($errorBag = $errors->getBag($bag))

<div class="flex flex-col gap-5">
    <x-ui.field label="Name" :for="$bag.'-name'" required :error="$errorBag->first('name')">
        <x-ui.input :id="$bag.'-name'" name="name" :value="old('name', $category?->name)" required maxlength="100" :invalid="$errorBag->has('name')" />
    </x-ui.field>
    <x-ui.field label="Description" :for="$bag.'-description'" hint="Shown to employees when they choose a category." :error="$errorBag->first('description')">
        <x-ui.input :id="$bag.'-description'" name="description" :value="old('description', $category?->description)" maxlength="255" :invalid="$errorBag->has('description')" />
    </x-ui.field>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.field label="Icon" :for="$bag.'-icon'" required :error="$errorBag->first('icon')">
            <x-ui.select :id="$bag.'-icon'" name="icon">
                @foreach ($icons as $icon => $label)
                    <option value="{{ $icon }}" @selected(old('icon', $category?->icon ?? 'help') === $icon)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Position" :for="$bag.'-sort_order'" required hint="Lower numbers appear first." :error="$errorBag->first('sort_order')">
            <x-ui.input :id="$bag.'-sort_order'" name="sort_order" type="number" min="0" max="999" :value="old('sort_order', $category?->sort_order ?? 10)" required :invalid="$errorBag->has('sort_order')" />
        </x-ui.field>
    </div>
</div>
