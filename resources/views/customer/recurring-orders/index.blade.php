<x-layouts.app title="Repeat orders">
    <div class="card divide-y divide-gray-100">
        @forelse ($recurringOrders as $recurringOrder)
            <div class="flex flex-wrap items-center gap-4 p-4">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900">
                        {{ ['weekly' => 'Every week', 'biweekly' => 'Every 2 weeks', 'monthly' => 'Every month'][$recurringOrder->frequency] ?? 'Repeat order' }}
                        <span class="badge-muted ml-1">{{ $recurringOrder->is_active ? 'Active' : 'Paused' }}</span>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Next: {{ $recurringOrder->next_order_date->format('M j, Y') }} · {{ ucfirst($recurringOrder->delivery_method) }} · Cash payment</p>
                    <p class="mt-1 text-xs text-gray-500">{{ collect($recurringOrder->items)->map(fn ($item) => $item['product_name'].' × '.$item['quantity'])->join(', ') }}</p>
                    @if ($recurringOrder->last_error)
                        <p class="mt-2 text-xs text-amber-700">Last attempt needs attention. Check product availability and your saved address.</p>
                    @endif
                    @if ($recurringOrder->lastOrder)
                        <a class="mt-2 inline-block text-xs text-brand-700 hover:underline" href="{{ route('customer.orders.show', $recurringOrder->lastOrder) }}">Latest order {{ $recurringOrder->lastOrder->order_number }}</a>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($recurringOrder->is_active)
                        <form method="POST" action="{{ route('customer.recurring.skip', $recurringOrder) }}">
                            @csrf
                            <button class="btn-secondary btn-sm">Skip next</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('customer.recurring.toggle', $recurringOrder) }}">
                        @csrf @method('PATCH')
                        <button class="btn-ghost btn-sm">{{ $recurringOrder->is_active ? 'Pause' : 'Resume' }}</button>
                    </form>
                </div>
            </div>
        @empty
            <x-empty-state title="No repeat orders" description="Schedule a weekly, biweekly, or monthly repeat from a completed order." />
        @endforelse
    </div>
    <div class="mt-4">{{ $recurringOrders->links() }}</div>
</x-layouts.app>