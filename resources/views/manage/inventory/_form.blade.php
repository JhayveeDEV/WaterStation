<div>
    <label class="label" for="name">Product name</label>
    <input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
    @error('name') <p class="field-error">{{ $message }}</p> @enderror
</div>
<div>
    <label class="label" for="description">Description</label>
    <textarea id="description" name="description" rows="2" class="input">{{ old('description', $product->description ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="container_type">Container type</label>
        <select id="container_type" name="container_type" class="input" required>
            @foreach (['Round Gallon', 'Slim Gallon', 'Other Gallon'] as $type)
                <option value="{{ $type }}" {{ old('container_type', $product->container_type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="unit-display">Unit</label>
        <input id="unit-display" type="text" value="gallon" class="input" disabled>
        <input type="hidden" name="unit" value="gallon">
        <p class="hint">This shop sells water in gallons only.</p>
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="price">Price</label>
        <div class="relative">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">₱</span>
            <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price ?? '') }}" class="input pl-7 {{ $errors->has('price') ? 'input-error' : '' }}" required>
        </div>
        @error('price') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="image">Photo</label>
        <div class="flex items-center gap-3">
            @if (isset($product->image_url) && $product->image_url)
                <img src="{{ $product->image_url }}" alt="" class="h-10 w-10 shrink-0 rounded-md object-cover">
            @endif
            <input id="image" type="file" name="image" accept="image/*"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
        </div>
        @error('image') <p class="field-error">{{ $message }}</p> @enderror
    </div>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label" for="quantity_on_hand">Stock on hand</label>
        <input id="quantity_on_hand" type="number" name="quantity_on_hand" value="{{ old('quantity_on_hand', $product->inventory->quantity_on_hand ?? 0) }}" class="input {{ $errors->has('quantity_on_hand') ? 'input-error' : '' }}" required>
        @error('quantity_on_hand') <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="label" for="reorder_level">Reorder level</label>
        <input id="reorder_level" type="number" name="reorder_level" value="{{ old('reorder_level', $product->inventory->reorder_level ?? 10) }}" class="input {{ $errors->has('reorder_level') ? 'input-error' : '' }}" required>
        <p class="hint">Stock at or below this shows as low.</p>
        @error('reorder_level') <p class="field-error">{{ $message }}</p> @enderror
    </div>
</div>
<label class="flex items-center gap-2 text-sm text-gray-600">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
    Active (visible in the shop)
</label>
