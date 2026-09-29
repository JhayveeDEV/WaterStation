<x-layouts.app title="Order board">
    @php
        $dots = ['ok' => 'bg-brand-500', 'info' => 'bg-blue-500', 'warn' => 'bg-amber-500', 'bad' => 'bg-red-500'];
    @endphp
    @php
        $activeOrderCount = $orders->flatten()->count();
        $deliveryCount = $orders->flatten()->where('delivery_method', 'delivery')->count();
    @endphp
    <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-base font-semibold text-slate-900">Live order flow</p>
            <p class="mt-1 text-sm text-slate-500">Move from confirmation to handoff with every order in view.</p>
        </div>
        <div class="flex items-center gap-4 text-sm text-slate-600">
            <span><strong class="text-slate-900">{{ $activeOrderCount }}</strong> active</span>
            <span><strong class="text-slate-900">{{ $deliveryCount }}</strong> delivery</span>
        </div>
    </div>
    {{-- Hooks used by the live-update JS: #order-board, [data-column], [data-count], #col-{status}, [data-order-row], [data-empty] --}}
    <div id="order-board" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @foreach ($columns as $status)
            @php $group = $orders->get($status->value, collect()); @endphp
            <div data-column class="flex min-h-[280px] flex-col rounded-2xl border border-slate-200 bg-slate-100/70 p-2.5 shadow-sm">
                <div class="mb-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="flex min-w-0 items-center gap-2 text-[0.72rem] font-bold uppercase tracking-[0.1em] text-slate-600">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $dots[$status->tone()] ?? 'bg-gray-400' }}" aria-hidden="true"></span>
                            <span class="truncate">{{ $status->label() }}</span>
                        </p>
                        <span data-count class="num inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-slate-100 px-2 text-xs font-bold text-slate-600">{{ $group->count() }}</span>
                    </div>
                </div>
                <div id="col-{{ $status->value }}" class="flex flex-1 flex-col gap-2">
                    @forelse ($group as $order)
                        <a href="{{ route('manage.orders.show', $order) }}" data-order-row="{{ $order->id }}"
                           class="group block rounded-xl border border-slate-200 border-l-4 border-l-{{ $status->tone() === 'warn' ? 'amber' : ($status->tone() === 'ok' ? 'emerald' : ($status->tone() === 'bad' ? 'red' : 'sky')) }}-400 bg-white p-3 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <p class="truncate text-sm font-bold text-slate-900 group-hover:text-brand-700">{{ $order->order_number }}</p>
                                <p class="shrink-0 text-sm font-bold text-slate-900">₱{{ number_format($order->total_amount, 2) }}</p>
                            </div>
                            <p class="mt-1 truncate text-xs font-medium text-slate-600">{{ $order->customer->name }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5 text-[11px] font-medium text-slate-500">
                                <span class="rounded-md bg-slate-100 px-2 py-1">{{ $order->isDelivery() ? 'Delivery' : 'Pickup' }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-1">{{ $order->schedule_date->format('M j') }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-1">{{ $order->items->sum('quantity') }} item{{ $order->items->sum('quantity') === 1 ? '' : 's' }}</span>
                            </div>
                            @if ($order->isDelivery())
                                <p class="mt-2 text-[11px] font-medium {{ $order->hasLocationPin() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $order->hasLocationPin() ? 'Pinned location available' : 'Address only' }}</p>
                            @endif
                        </a>
                    @empty
                        <p data-empty class="rounded-xl border border-dashed border-slate-300 bg-white/50 px-3 py-5 text-center text-xs font-medium text-slate-400">Nothing here yet</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>
