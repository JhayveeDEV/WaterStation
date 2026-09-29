<x-layouts.app title="My deliveries">
    <div class="card divide-y divide-gray-100">
        @forelse ($deliveries as $delivery)
            <a href="{{ route('rider.deliveries.show', $delivery->order) }}" class="flex items-center justify-between gap-4 p-4 transition-colors hover:bg-gray-50">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $delivery->order->order_number }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $delivery->order->customer->name }}, {{ $delivery->order->contact_phone }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <x-status-badge :status="$delivery->order->status" />
                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </div>
            </a>
        @empty
            <x-empty-state title="No deliveries yet" description="Deliveries assigned to you will be listed here." />
        @endforelse
    </div>
    <div class="mt-4">{{ $deliveries->links() }}</div>
</x-layouts.app>
