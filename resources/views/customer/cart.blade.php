<x-layouts.app title="Cart">
    @if (empty($lines))
        <x-empty-state card title="Your cart is empty" description="Browse the shop to add water refills or bottles.">
            <a href="{{ route('customer.shop') }}" class="btn-primary">Go to shop</a>
        </x-empty-state>
    @else
        @php $cartTotal = collect($lines)->sum('subtotal'); @endphp
        <div class="grid items-start gap-6 lg:grid-cols-3">
            <div class="card divide-y divide-gray-100 lg:col-span-2">
                @foreach ($lines as $line)
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-3 p-4">
                        <div class="min-w-[10rem] flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $line['product']->name }}</p>
                            <p class="num text-xs text-gray-500">₱{{ number_format($line['product']->price, 2) }} each</p>
                        </div>
                        <form method="POST" action="{{ route('customer.cart.update', $line['product']) }}">
                            @csrf @method('PUT')
                            <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ max(1, $line['product']->stock) }}"
                                   aria-label="Quantity for {{ $line['product']->name }}" class="input num w-20 text-center" onchange="this.form.submit()">
                        </form>
                        <p class="num w-24 text-right text-sm font-semibold text-gray-900">₱{{ number_format($line['subtotal'], 2) }}</p>
                        <form method="POST" action="{{ route('customer.cart.remove', $line['product']) }}">
                            @csrf @method('DELETE')
                            <button class="btn-ghost p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" aria-label="Remove {{ $line['product']->name }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-7 0 1 12a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-12"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="card p-5 lg:sticky lg:top-20">
                <h3 class="card-title mb-4">Order total</h3>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="num font-semibold text-gray-900">₱{{ number_format($cartTotal, 2) }}</span>
                </div>
                <p class="mt-2 text-xs text-gray-400">Delivery fee is added at checkout based on your address.</p>
                <a href="{{ route('customer.checkout') }}" class="btn-primary mt-5 w-full py-2.5">Proceed to checkout</a>
                <a href="{{ route('customer.shop') }}" class="mt-2 block text-center text-sm text-gray-500 hover:text-gray-700">Keep shopping</a>
            </div>
        </div>
    @endif
</x-layouts.app>
