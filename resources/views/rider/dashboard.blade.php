<x-layouts.app title="Dashboard">
    <div class="card mb-6 flex flex-wrap items-center justify-between gap-4 p-4">
        <div class="flex items-center gap-3">
            <span class="h-2.5 w-2.5 rounded-full {{ $rider->is_available ? 'bg-brand-500' : 'bg-gray-300' }}" aria-hidden="true"></span>
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $rider->is_available ? "You're available for deliveries" : "You're offline" }}</p>
                <p class="text-xs text-gray-500">{{ $rider->is_available ? 'Go offline when you stop taking new deliveries.' : 'Go online to receive new deliveries.' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('rider.availability') }}">
            @csrf
            <button class="{{ $rider->is_available ? 'btn-secondary' : 'btn-primary' }}">
                {{ $rider->is_available ? 'Go offline' : 'Go online' }}
            </button>
        </form>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="stat"><p class="stat-label">Active deliveries</p><p class="stat-value">{{ $active->count() }}</p></div>
        <div class="stat"><p class="stat-label">Completed today</p><p class="stat-value">{{ $completedToday }}</p></div>
    </div>

    <h3 class="mb-3 text-sm font-semibold text-gray-900">Assigned to you</h3>
    <div class="card divide-y divide-gray-100">
        @forelse ($active as $delivery)
            <a href="{{ route('rider.deliveries.show', $delivery->order) }}" class="flex items-center justify-between gap-4 p-4 transition-colors hover:bg-gray-50">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ $delivery->order->order_number }}</p>
                    <p class="mt-0.5 text-sm text-gray-600">{{ $delivery->order->customer->name }}</p>
                    <p class="truncate text-xs text-gray-500">{{ $delivery->order->delivery_address }}</p>
                    <p class="text-xs text-gray-400">{{ $delivery->order->contact_phone }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <x-status-badge :status="$delivery->order->status" />
                    <svg class="h-4 w-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                </div>
            </a>
        @empty
            <x-empty-state title="No active deliveries" description="New assignments will show up here." />
        @endforelse
    </div>
</x-layouts.app>
