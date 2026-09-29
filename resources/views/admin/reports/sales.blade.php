<x-layouts.app title="Sales report">
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="label" for="from">From</label>
            <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input">
        </div>
        <div>
            <label class="label" for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input">
        </div>
        <button class="btn-primary">Apply</button>
        <a href="{{ route($exportRoute, ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="btn-secondary sm:ml-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 11l5 5 5-5M5 20h14"/></svg>
            Export CSV
        </a>
    </form>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="stat"><p class="stat-label">Paid orders</p><p class="stat-value">{{ number_format($summary['orders']) }}</p></div>
        <div class="stat"><p class="stat-label">Revenue</p><p class="stat-value">₱{{ number_format($summary['revenue'], 2) }}</p></div>
        <div class="stat"><p class="stat-label">Average order</p><p class="stat-value">₱{{ number_format($summary['average'], 2) }}</p></div>
        <div class="stat"><p class="stat-label">Outstanding</p><p class="stat-value">₱{{ number_format($summary['outstanding_amount'], 2) }}</p><p class="text-xs text-gray-500">{{ number_format($summary['outstanding_orders']) }} unpaid orders</p></div>
        <div class="stat"><p class="stat-label">Delivered</p><p class="stat-value">{{ number_format($summary['delivered']) }}</p></div>
        <div class="stat"><p class="stat-label">Average delivery time</p><p class="stat-value">{{ $summary['average_delivery_minutes'] === null ? '—' : number_format($summary['average_delivery_minutes']).' min' }}</p></div>
    </div>

    <div class="card mb-6 overflow-x-auto">
        <div class="card-header"><h3 class="card-title">Top products</h3><span class="text-xs text-gray-500">Paid orders in this period</span></div>
        <table class="data-table">
            <thead><tr><th>Product</th><th class="text-right">Units sold</th><th class="text-right">Sales</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($productSales as $product)
                    <tr>
                        <td class="font-medium text-gray-900">{{ $product->product_name }}</td>
                        <td class="num text-right text-gray-600">{{ number_format($product->units_sold) }}</td>
                        <td class="num text-right font-medium text-gray-900">₱{{ number_format($product->revenue, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-sm text-gray-400">No paid product sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="font-medium text-gray-900">{{ $order->order_number }}</td>
                        <td class="text-gray-600">{{ $order->customer->name }}</td>
                        <td class="text-gray-500">{{ $order->created_at->format('M j, Y') }}</td>
                        <td class="num text-right font-medium text-gray-900">₱{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-gray-400">No paid orders between these dates. Try a wider range.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
