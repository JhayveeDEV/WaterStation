<x-layouts.app title="My orders">
    <div class="card divide-y divide-gray-100">
        @forelse ($orders as $order)
            <div data-order-row="{{ $order->id }}" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 p-4 transition-colors hover:bg-gray-50">
                <a href="{{ route('customer.orders.show', $order) }}" class="min-w-[10rem] flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ $order->order_number }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('item', $order->items->count()) }}, {{ $order->schedule_label }}</p>
                </a>
                <div class="flex items-center gap-3 sm:gap-4">
                    <p class="num text-sm font-semibold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</p>
                    <x-status-badge :status="$order->status" />
                    @if ($order->status->isFinal() && auth()->user()->can('place orders'))
                        <form method="POST" action="{{ route('customer.orders.reorder', $order) }}">
                            @csrf
                            <button class="btn-secondary btn-sm">Reorder</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <x-empty-state title="No orders yet" description="Your placed orders will show up here.">
                <a href="{{ route('customer.shop') }}" class="btn-primary">Go to shop</a>
            </x-empty-state>
        @endforelse
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.app>
