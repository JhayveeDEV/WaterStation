<x-layouts.app title="Inventory report">
    <div class="card divide-y divide-gray-100">
        @forelse ($movements as $m)
            @php $in = $m->quantity >= 0; @endphp
            <div class="flex items-center gap-3 p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $in ? 'bg-brand-50 text-brand-700' : 'bg-red-50 text-red-600' }}" aria-hidden="true">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="{{ $in ? 'M12 19V5M5 12l7-7 7 7' : 'M12 5v14M19 12l-7 7-7-7' }}"/>
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-900">{{ $m->product->name }} <span class="badge-muted ml-1">{{ ucfirst($m->type) }}</span></p>
                    <p class="text-xs text-gray-500">
                        {{ $m->user?->name ?? 'System' }}
                        {{ $m->supplier_name ? ' · Supplier: '.$m->supplier_name : '' }}
                        {{ $m->unit_cost !== null ? ' · Unit cost ₱'.number_format((float) $m->unit_cost, 2) : '' }}
                        · {{ $m->created_at->format('M j, Y g:i A') }}
                    </p>
                </div>
                <p class="num text-sm font-semibold {{ $in ? 'text-brand-700' : 'text-red-600' }}">{{ $in ? '+' : '' }}{{ $m->quantity }}</p>
            </div>
        @empty
            <x-empty-state title="No stock movements yet" />
        @endforelse
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
</x-layouts.app>
