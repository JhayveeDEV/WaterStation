<x-layouts.app title="Stock history: {{ $product->name }}">
    <a href="{{ route('manage.inventory.index') }}" class="mb-3 inline-block text-sm text-gray-500 hover:text-gray-700">&larr; Back to inventory</a>
    @can('manage inventory')
        <form method="POST" action="{{ route('manage.inventory.stock', $product) }}" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <input type="hidden" name="type" value="received">
            <div>
                <label class="label" for="receive-quantity">Quantity received</label>
                <input id="receive-quantity" type="number" name="quantity" min="1" class="input" required>
            </div>
            <div>
                <label class="label" for="supplier-name">Supplier</label>
                <input id="supplier-name" name="supplier_name" class="input" maxlength="120">
            </div>
            <div>
                <label class="label" for="unit-cost">Unit cost</label>
                <input id="unit-cost" type="number" name="unit_cost" min="0" step="0.01" class="input">
            </div>
            <div>
                <label class="label" for="receive-reason">Note</label>
                <input id="receive-reason" name="reason" class="input" maxlength="255">
            </div>
            <div class="flex items-end"><button class="btn-primary w-full">Record restock</button></div>
        </form>
    @endcan
    <div class="card divide-y divide-gray-100">
        @forelse ($movements as $m)
            @php $in = $m->quantity >= 0; @endphp
            <div class="flex items-center gap-3 p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $in ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' }}" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $in ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M19 12l-7 7-7-7' }}"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-900">{{ ucfirst($m->type) }} <span class="text-gray-400">by {{ $m->user?->name ?? 'System' }}</span></p>
                    <p class="text-xs text-gray-500">
                        {{ $m->supplier_name ? 'Supplier: '.$m->supplier_name.' · ' : '' }}
                        {{ $m->unit_cost !== null ? 'Unit cost ₱'.number_format((float) $m->unit_cost, 2).' · ' : '' }}
                        {{ $m->reason ? $m->reason.' · ' : '' }}{{ $m->created_at->format('M j, Y g:i A') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="num text-sm font-semibold {{ $in ? 'text-brand-700' : 'text-red-600' }}">{{ $in ? '+' : '' }}{{ $m->quantity }}</p>
                    <p class="num text-xs text-gray-400">Balance {{ $m->balance_after }}</p>
                </div>
            </div>
        @empty
            <x-empty-state title="No stock movements yet" description="Receiving, damaged and adjusted stock will be logged here." />
        @endforelse
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</x-layouts.app>
