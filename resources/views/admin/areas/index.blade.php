<x-layouts.app title="Delivery areas">
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,20rem)_1fr]">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add delivery area</h3></div>
            <form method="POST" action="{{ route('admin.areas.store') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="label" for="new-name">Area name</label>
                    <input id="new-name" name="name" value="{{ old('name') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="new-fee">Delivery fee</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">₱</span>
                        <input id="new-fee" type="number" step="0.01" min="0" name="fee" value="{{ old('fee') }}" class="input pl-7 {{ $errors->has('fee') ? 'input-error' : '' }}" required>
                    </div>
                    @error('fee') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="btn-primary w-full">Add area</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Your areas</h3><span class="text-xs text-gray-500">Fee in ₱</span></div>
            <div class="divide-y divide-gray-100">
                @forelse ($areas as $area)
                    <form method="POST" action="{{ route('admin.areas.update', $area) }}" class="flex flex-wrap items-center gap-3 p-4">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $area->name }}" class="input min-w-[10rem] flex-1" aria-label="Area name">
                        <input type="number" step="0.01" min="0" name="fee" value="{{ $area->fee }}" class="input num w-24" aria-label="Delivery fee">
                        <label class="flex items-center gap-1.5 text-xs text-gray-600">
                            <input type="checkbox" name="is_active" value="1" {{ $area->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            Active
                        </label>
                        <button class="btn-secondary btn-sm">Save</button>
                    </form>
                @empty
                    <x-empty-state title="No delivery areas yet" description="Add your first area to start taking delivery orders." />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
