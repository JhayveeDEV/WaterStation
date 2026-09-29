<x-layouts.app title="Dashboard">
    <div id="live-stats" class="mb-2 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat"><p class="stat-label">Pending orders</p><p data-stat="pending" class="stat-value">{{ $stats['pending'] }}</p></div>
        <div class="stat"><p class="stat-label">In progress</p><p data-stat="in_progress" class="stat-value">{{ $stats['in_progress'] }}</p></div>
        <div class="stat"><p class="stat-label">Completed today</p><p data-stat="completed_today" class="stat-value">{{ $stats['completed_today'] }}</p></div>
        <div class="stat"><p class="stat-label">Revenue today</p><p class="stat-value">₱{{ number_format($stats['revenue_today'], 2) }}</p></div>
    </div>
    <p class="mb-6 text-xs text-gray-400">Order counts update live. Revenue refreshes when you reload the page.</p>

    <div class="grid items-start gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent orders</h3>
                <a href="{{ route('manage.orders.board') }}" class="text-xs font-medium text-brand-600 hover:underline">Open board</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($recentOrders as $order)
                    <a href="{{ route('manage.orders.show', $order) }}" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-gray-50">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $order->order_number }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $order->customer->name }}</p>
                        </div>
                        <x-status-badge :status="$order->status" />
                    </a>
                @empty
                    <x-empty-state title="No orders yet" description="New orders will appear here as they come in." />
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Low stock</h3>
                <a href="{{ route('manage.inventory.index') }}" class="text-xs font-medium text-brand-600 hover:underline">Open inventory</a>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse ($lowStock as $inv)
                    <div class="flex items-center justify-between gap-3 p-4">
                        <p class="text-sm text-gray-900">{{ $inv->product->name }}</p>
                        <span class="badge-warn badge-dot num">{{ $inv->quantity_on_hand }} left</span>
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-gray-400">Stock levels look fine.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
