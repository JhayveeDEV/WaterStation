<x-layouts.app title="Dashboard">
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat"><p class="stat-label">Total users</p><p class="stat-value">{{ number_format($stats['total_users']) }}</p></div>
        <div class="stat"><p class="stat-label">Customers</p><p class="stat-value">{{ number_format($stats['total_customers']) }}</p></div>
        <div class="stat"><p class="stat-label">Orders this month</p><p data-stat="orders_this_month" class="stat-value">{{ number_format($stats['orders_this_month']) }}</p></div>
        <div class="stat"><p class="stat-label">Revenue this month</p><p class="stat-value">₱{{ number_format($stats['revenue_this_month'], 2) }}</p></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Orders by status</h3></div>
            <div class="space-y-3 p-5">
                @php $totalOrders = max(1, collect($ordersByStatus)->sum()); @endphp
                @forelse ($ordersByStatus as $status => $count)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-gray-600">{{ \App\Enums\OrderStatus::from($status)->label() }}</span>
                            <span class="num font-medium text-gray-900">{{ $count }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-gray-100">
                            <div class="h-1.5 rounded-full bg-brand-500" style="width: {{ max(3, ($count / $totalOrders) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-gray-400">No orders yet.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Revenue, last 14 days</h3>
                @unless ($revenueByDay->isEmpty())
                    <span class="num text-xs text-gray-500">Total ₱{{ number_format($revenueByDay->sum('total'), 0) }}</span>
                @endunless
            </div>
            <div class="p-5">
                @if ($revenueByDay->isEmpty())
                    <p class="py-4 text-center text-sm text-gray-400">No paid orders in this period yet.</p>
                @else
                    @php $max = max(1, $revenueByDay->max('total')); @endphp
                    <div class="space-y-2">
                        @foreach ($revenueByDay as $row)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-12 shrink-0 text-gray-500">{{ \Illuminate\Support\Carbon::parse($row->day)->format('M j') }}</span>
                                <div class="h-2.5 flex-1 rounded-full bg-gray-100">
                                    <div class="h-2.5 rounded-full bg-brand-600" style="width: {{ max(3, ($row->total / $max) * 100) }}%"></div>
                                </div>
                                <span class="num w-16 shrink-0 text-right font-medium text-gray-700">₱{{ number_format($row->total, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
