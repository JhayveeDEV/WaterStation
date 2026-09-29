<x-layouts.app title="Edit product">
    <div class="max-w-lg space-y-6">
        <div>
            <a href="{{ route('manage.inventory.index') }}" class="mb-3 inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to inventory</a>
            <form method="POST" action="{{ route('manage.inventory.update', $product) }}" enctype="multipart/form-data" class="card space-y-4 p-6">
                @csrf @method('PUT')
                @include('manage.inventory._form')
                <div class="flex gap-2 pt-2">
                    <button class="btn-primary flex-1">Save changes</button>
                    <a href="{{ route('manage.inventory.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Adjust stock</h3>
                <a href="{{ route('manage.inventory.movements', $product) }}" class="text-xs font-medium text-brand-600 hover:underline">View history</a>
            </div>
            <form method="POST" action="{{ route('manage.inventory.stock', $product) }}" class="flex flex-wrap items-end gap-3 p-5">
                @csrf
                <div class="min-w-[9rem] flex-1">
                    <label class="label" for="stock-type">Type</label>
                    <select id="stock-type" name="type" class="input">
                        <option value="received">Received</option>
                        <option value="damaged">Damaged</option>
                        <option value="adjusted">Adjustment</option>
                    </select>
                </div>
                <div class="w-24">
                    <label class="label" for="stock-qty">Qty</label>
                    <input id="stock-qty" type="number" name="quantity" class="input num" required>
                </div>
                <button class="btn-secondary">Apply</button>
            </form>
        </div>
    </div>
</x-layouts.app>
