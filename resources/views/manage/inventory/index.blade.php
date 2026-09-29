<x-layouts.app title="Inventory">
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-gray-500">{{ $products->count() }} {{ \Illuminate\Support\Str::plural('product', $products->count()) }}</p>
        @can('manage inventory')
            <a href="{{ route('manage.inventory.create') }}" class="btn-primary">Add product</a>
        @endcan
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $product)
                    @php $low = $product->inventory?->isLow() ?? true; @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md bg-brand-50">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <svg class="h-5 w-5 text-brand-200" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.8.4C9 6 5.5 10 5.5 14a6.5 6.5 0 0013 0c0-4-3.5-8-5.7-11.1a1 1 0 00-.8-.4z"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $product->container_type }}, {{ $product->unit }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="num text-gray-700">₱{{ number_format($product->price, 2) }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="num {{ $low ? 'font-medium text-amber-600' : 'text-gray-700' }}">{{ $product->inventory?->quantity_on_hand ?? 0 }}</span>
                                @if ($low) <span class="badge-warn">Low</span> @endif
                            </div>
                        </td>
                        <td>
                            <span class="{{ $product->is_active ? 'badge-success' : 'badge-muted' }} badge-dot">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('manage.inventory.movements', $product) }}" class="btn-ghost btn-sm">History</a>
                            @can('manage inventory')
                                <a href="{{ route('manage.inventory.edit', $product) }}" class="btn-ghost btn-sm text-brand-700">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No products yet. Add your first one to start selling.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
