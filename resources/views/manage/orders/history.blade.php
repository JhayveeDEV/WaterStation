<x-layouts.app title="Order history">
    <form method="GET" class="mb-4 flex max-w-md gap-2">
        <div class="relative flex-1">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M21 21l-4.3-4.3M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/></svg>
            <input name="search" value="{{ request('search') }}" placeholder="Search order number" class="input pl-9" aria-label="Search order number">
        </div>
        <button class="btn-secondary">Search</button>
        @if (request('search'))
            <a href="{{ route('manage.orders.history') }}" class="btn-ghost">Clear</a>
        @endif
    </form>

    <div class="card divide-y divide-gray-100">
        @forelse ($orders as $order)
            <a href="{{ route('manage.orders.show', $order) }}" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 p-4 transition-colors hover:bg-gray-50">
                <div class="min-w-[10rem] flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ $order->order_number }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $order->customer->name }}, {{ $order->updated_at->format('M j, Y g:i A') }}</p>
                </div>
                <div class="flex items-center gap-4">
                    <p class="num text-sm font-semibold text-gray-900">₱{{ number_format($order->total_amount, 2) }}</p>
                    <x-status-badge :status="$order->status" />
                </div>
            </a>
        @empty
            <x-empty-state title="{{ request('search') ? 'No orders match that number' : 'No completed or cancelled orders yet' }}" />
        @endforelse
    </div>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-layouts.app>
