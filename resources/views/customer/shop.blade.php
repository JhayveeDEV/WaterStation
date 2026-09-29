<x-layouts.app title="Shop">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            @php $low = $product->stock > 0 && $product->stock <= 5; @endphp
            <div class="card flex flex-col overflow-hidden" data-product-row="{{ $product->id }}">
                <div class="flex aspect-[4/3] items-center justify-center bg-brand-50">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <svg class="h-12 w-12 text-brand-200" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.8.4C9 6 5.5 10 5.5 14a6.5 6.5 0 0013 0c0-4-3.5-8-5.7-11.1a1 1 0 00-.8-.4z"/></svg>
                    @endif
                </div>
                <div class="flex flex-1 flex-col p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $product->name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ $product->container_type }}, {{ $product->unit }}</p>
                        </div>
                        <p class="num shrink-0 text-base font-semibold text-brand-700">₱{{ number_format($product->price, 2) }}</p>
                    </div>
                    <p class="mt-2 text-xs {{ $product->stock <= 0 ? 'text-red-600' : ($low ? 'text-amber-600' : 'text-gray-400') }}" data-stock-label>
                        {{ $product->stock > 0 ? $product->stock.' in stock' : 'Out of stock' }}
                    </p>
                    @can('place orders')
                        <form method="POST" action="{{ route('customer.cart.add', $product) }}" class="mt-auto flex gap-2 pt-3" data-add-form>
                            @csrf
                            <input type="number" name="quantity" value="1" min="1" max="{{ max(1, $product->stock) }}" data-qty-input
                                   aria-label="Quantity for {{ $product->name }}" class="input num w-20 text-center" {{ $product->stock <= 0 ? 'disabled' : '' }}>
                            <button class="btn-primary flex-1" data-add-btn {{ $product->stock <= 0 ? 'disabled' : '' }}>
                                <span data-add-label>{{ $product->stock <= 0 ? 'Out of stock' : 'Add to cart' }}</span>
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <x-empty-state card title="No products available" description="Please check back later." />
            </div>
        @endforelse
    </div>
</x-layouts.app>
