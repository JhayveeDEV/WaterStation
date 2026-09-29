<x-layouts.app title="Order {{ $order->order_number }}">
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-500">{{ $order->method_label }}</p>
                    </div>
                    <x-status-badge :status="$order->status" />
                </div>

                <dl class="grid gap-x-6 gap-y-4 p-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500">Customer</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $order->customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Phone</dt>
                        <dd class="num mt-0.5"><a href="tel:{{ $order->contact_phone }}" class="text-brand-600 hover:underline">{{ $order->contact_phone }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Scheduled</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $order->schedule_label }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Payment</dt>
                        <dd class="mt-0.5 flex flex-wrap items-center gap-2 text-gray-900">
                            {{ ucfirst($order->payment_method) }}
                            <span class="{{ $order->isPaid() ? 'badge-success' : 'badge-warn' }} badge-dot">{{ ucfirst($order->payment_status) }}</span>
                        </dd>
                        @if ($order->payment_method === 'gcash' && ! $order->isPaid())
                            <dd class="mt-1 text-xs text-gray-400">The customer hasn't completed GCash checkout yet.</dd>
                        @endif
                    </div>
                    @if ($order->isDelivery())
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-gray-500">Delivery address</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $order->delivery_address }}</dd>
                        </div>
                    @endif
                </dl>

                @if ($order->customer_notes || $order->cancel_reason)
                    <div class="space-y-2 px-5 pb-5">
                        @if ($order->customer_notes)
                            <div class="rounded-md border border-amber-200 bg-amber-50 p-3">
                                <p class="text-xs font-medium text-amber-800">Note from customer</p>
                                <p class="mt-0.5 text-sm text-amber-900">{{ $order->customer_notes }}</p>
                            </div>
                        @endif
                        @if ($order->cancel_reason)
                            <div class="rounded-md border border-red-200 bg-red-50 p-3">
                                <p class="text-xs font-medium text-red-800">Reason</p>
                                <p class="mt-0.5 text-sm text-red-700">{{ $order->cancel_reason }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="space-y-2.5 border-t border-gray-100 p-5">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-600">{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                            <span class="num text-gray-900">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between border-t border-gray-100 pt-3 text-base font-semibold">
                        <span>Total</span><span class="num">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">History</h3></div>
                <ol class="space-y-4 p-5">
                    @foreach ($order->histories as $h)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>
                            <div class="min-w-0">
                                <p class="text-sm">
                                    <span class="font-medium text-gray-900">{{ \App\Enums\OrderStatus::from($h->to_status)->label() }}</span>
                                    <span class="text-gray-500">by {{ $h->user?->name ?? 'System' }}</span>
                                </p>
                                <p class="text-xs text-gray-400">{{ $h->created_at->format('M j, g:i A') }}</p>
                                @if ($h->note) <p class="mt-1 text-xs italic text-gray-500">{{ $h->note }}</p> @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        {{-- Right column. Alpine state drives the "reason" dialog for cancel / reject. --}}
        <div class="space-y-6"
             x-data="{ open: false, status: '', label: '', note: '' }"
             x-init="$watch('open', v => v && $nextTick(() => $refs.reason.focus()))"
             @keydown.escape.window="open = false">

            @can('process orders')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Update status</h3></div>
                <div class="p-5">
                    @php $next = app(\App\Services\OrderService::class)->availableTransitions($order); @endphp
                    @if (empty($next))
                        <p class="text-sm text-gray-400">No further actions available.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($next as $statusValue)
                                @php $s = \App\Enums\OrderStatus::from($statusValue); $needsReason = in_array($statusValue, ['cancelled', 'rejected']); @endphp
                                @if ($needsReason)
                                    <button type="button" class="btn-danger w-full justify-start"
                                            @click="open = true; status = @js($statusValue); label = @js($s->label()); note = ''">
                                        Mark as {{ $s->label() }}
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('manage.orders.transition', $order) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $statusValue }}">
                                        <input type="hidden" name="note" value="">
                                        <button class="btn-secondary w-full justify-start">Mark as {{ $s->label() }}</button>
                                    </form>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endcan

            @can('assign riders')
            @if (in_array($order->status->value, ['confirmed', 'preparing']) && $order->isDelivery())
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Assign rider</h3></div>
                    <form method="POST" action="{{ route('manage.orders.assign-rider', $order) }}" class="space-y-3 p-5">
                        @csrf
                        <label class="sr-only" for="rider_id">Rider</label>
                        <select id="rider_id" name="rider_id" class="input" required>
                            <option value="">Select rider</option>
                            @foreach ($riders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->name }} {{ $rider->is_available ? '' : '(busy)' }}</option>
                            @endforeach
                        </select>
                        <button class="btn-primary w-full">Assign rider</button>
                    </form>
                </div>
            @endif
                @endcan

            @if ($order->delivery?->rider)
                <div class="card p-5">
                    <h3 class="card-title mb-3">Rider</h3>
                    <div class="flex items-center gap-3">
                        <span class="avatar">{{ strtoupper(mb_substr($order->delivery->rider->name, 0, 1)) }}</span>
                        <p class="text-sm text-gray-700">{{ $order->delivery->rider->name }}</p>
                    </div>
                </div>
            @endif

            @can('process orders')
            {{-- Reason dialog --}}
            <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/40 p-4 sm:items-center">
                <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl" @click.outside="open = false" role="dialog" aria-modal="true" aria-labelledby="reason-title">
                    <h3 id="reason-title" class="text-sm font-semibold text-gray-900" x-text="'Mark as ' + label"></h3>
                    <p class="mt-1 text-sm text-gray-500">The customer will see this reason.</p>
                    <form method="POST" action="{{ route('manage.orders.transition', $order) }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="status" :value="status">
                        <div>
                            <label class="label" for="reason">Reason</label>
                            <textarea id="reason" name="note" x-ref="reason" x-model="note" rows="3" required class="input" placeholder="For example: out of stock, or the customer asked to cancel"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-secondary" @click="open = false">Go back</button>
                            <button class="btn-danger" :disabled="note.trim() === ''" x-text="'Mark as ' + label"></button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan
        </div>
    </div>
</x-layouts.app>
