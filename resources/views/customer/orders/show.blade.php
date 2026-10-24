<x-layouts.app title="Order {{ $order->order_number }}">
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $order->order_number }}</p>
                        <p class="text-xs text-gray-500">{{ $order->method_label }}, {{ $order->schedule_label }}</p>
                    </div>
                    <x-status-badge :status="$order->status" />
                </div>

                <div class="p-5">
                    @php
                        $steps = collect($order->timeline());
                        $currentIndex = $order->status->isFinal() ? null : $steps->search(fn ($s) => ! $s['done']);
                    @endphp
                    <ol>
                        @foreach ($steps as $i => $step)
                            <li class="relative flex items-start gap-3 {{ $loop->last ? '' : 'pb-6' }}">
                                @unless ($loop->last)
                                    <span class="absolute left-[9px] top-6 bottom-0 w-px {{ $step['done'] ? 'bg-brand-600' : 'bg-gray-200' }}" aria-hidden="true"></span>
                                @endunless
                                @if ($step['done'])
                                    <span class="relative flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                                    </span>
                                @else
                                    <span class="relative h-5 w-5 shrink-0 rounded-full border-2 bg-white {{ $currentIndex === $i ? 'border-brand-500 ring-4 ring-brand-50' : 'border-gray-200' }}"></span>
                                @endif
                                <div class="-mt-0.5">
                                    <p class="text-sm font-medium {{ $step['done'] || $currentIndex === $i ? 'text-gray-900' : 'text-gray-400' }}">{{ $step['label'] }}</p>
                                    @if ($step['time'])
                                        <p class="text-xs text-gray-400">{{ $step['time']->format('M j, g:i A') }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($order->cancel_reason)
                        <p class="mt-5 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $order->cancel_reason }}</p>
                    @endif

                    @php
                        $canCancel = $order->status->value === 'pending';
                        $canReorder = $order->status->isFinal();
                        $canReceipt = $order->isPaid() || in_array($order->status->value, ['delivered', 'completed']);
                    @endphp
                    @if ($canCancel || $canReorder || $canReceipt)
                        <div class="mt-6 flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                            @if ($canCancel)
                                <form method="POST" action="{{ route('customer.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                                    @csrf
                                    <button class="btn-danger">Cancel order</button>
                                </form>
                            @endif
                            @if ($canReorder && auth()->user()->can('place orders'))
                                <form method="POST" action="{{ route('customer.orders.reorder', $order) }}">
                                    @csrf
                                    <button class="btn-primary">Order this again</button>
                                </form>
                            @endif
                            @if ($canReceipt)
                                <a href="{{ route('customer.orders.receipt', $order) }}" class="btn-secondary">Download receipt (PDF)</a>
                            @endif
                        </div>
                    @endif
                    @if (in_array($order->status->value, ['delivered', 'completed'], true) && auth()->user()->can('place orders'))
                        <form method="POST" action="{{ route('customer.orders.recurring.store', $order) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-5">
                            @csrf
                            <div class="min-w-44 flex-1">
                                <label class="label" for="repeat-frequency">Repeat this order</label>
                                <select id="repeat-frequency" name="frequency" class="input" required>
                                    <option value="weekly">Every week</option>
                                    <option value="biweekly">Every 2 weeks</option>
                                    <option value="monthly">Every month</option>
                                </select>
                            </div>
                            <button class="btn-secondary">Schedule repeat</button>
                            <p class="w-full text-xs text-gray-500">Each repeat uses cash payment; online payment is not charged automatically. You can pause or skip it anytime.</p>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Items</h3></div>
                <div class="space-y-2.5 p-5">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-600">{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                            <span class="num text-gray-900">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <h3 class="card-title mb-3">Summary</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="num">₱{{ number_format($order->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Delivery fee</span><span class="num">₱{{ number_format($order->delivery_fee, 2) }}</span></div>
                    <div class="mt-1.5 flex justify-between border-t border-gray-100 pt-2 text-base font-semibold"><span>Total</span><span class="num">₱{{ number_format($order->total_amount, 2) }}</span></div>
                </div>
                <p class="mt-3 text-xs text-gray-500" data-payment-status>
                    Payment: {{ $order->payment_method_label }} · {{ ucfirst($order->payment_status) }}
                </p>
                @if ($order->payment?->provider === 'paymongo' && ! $order->isPaid())
                    <p class="mt-2 text-sm text-amber-700">We check your payment with PayMongo whenever you open this order. If you just paid and it still shows unpaid, refresh in a moment. Please do not pay again.</p>
                @elseif (request('payment') === 'cancelled')
                    <p class="mt-2 text-sm text-gray-600">Payment was not completed. You can try again from this order.</p>
                @endif
                @if ($order->isAwaitingOnlinePayment())
                    <a href="{{ route('customer.payments.initiate', $order) }}" class="btn-primary mt-3 w-full">Continue to {{ $order->payment_method_label }} payment</a>
                    <p class="mt-2 text-xs text-gray-500">Scan the QR Ph code on the secure checkout with a supported banking or e-wallet app.</p>
                @endif
            </div>

            @if ($order->isDelivery())
                <div class="card p-5">
                    <h3 class="card-title mb-2">Delivery</h3>
                    <p class="text-sm text-gray-600">{{ $order->delivery_address }}</p>
                    @if ($order->status->value === 'out_for_delivery')
                        <div class="mt-3 rounded-md border border-brand-200 bg-brand-50 px-3 py-2">
                            <p class="text-xs font-medium text-brand-800">Expected delivery window</p>
                            <p class="mt-0.5 text-sm text-brand-900">{{ $order->schedule_date->format('M j, Y') }}{{ $order->schedule_slot ? ' · '.\App\Models\Order::slotLabel($order->schedule_slot) : '' }}</p>
                        </div>
                    @endif
                    @if ($order->delivery?->rider)
                        <p class="mt-2 text-xs text-gray-500">Rider: <span class="font-medium text-gray-700">{{ $order->delivery->rider->name }}</span></p>
                    @endif
                </div>

                @if ($order->status->value === 'out_for_delivery')
                    <div class="card p-4">
                        <h3 class="card-title mb-3 px-1 pt-1">Rider location</h3>
                        <div id="rider-map" class="isolate h-56 rounded-md bg-gray-100"
                             data-lat="{{ $order->delivery->last_lat ?? '' }}"
                             data-lng="{{ $order->delivery->last_lng ?? '' }}"></div>
                        <p id="rider-map-empty" class="px-1 pb-1 pt-2 text-xs text-gray-500 {{ $order->delivery->last_lat ? 'hidden' : '' }}">
                            Waiting for the rider to turn on location sharing…
                        </p>
                    </div>

                    @push('head')
                        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    @endpush
                    @push('scripts')
                        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                        <script>
                            (function () {
                                const el = document.getElementById('rider-map');
                                if (!el || typeof L === 'undefined') return;

                                const emptyLabel = document.getElementById('rider-map-empty');
                                const startLat = parseFloat(el.dataset.lat) || 7.0731;   // Davao City as a sane default center
                                const startLng = parseFloat(el.dataset.lng) || 125.6128;

                                const map = L.map(el, { zoomControl: false }).setView([startLat, startLng], 14);
                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; OpenStreetMap contributors',
                                    maxZoom: 19,
                                }).addTo(map);

                                let marker = el.dataset.lat ? L.marker([startLat, startLng]).addTo(map) : null;

                                if (window.Echo) {
                                    window.Echo.private('users.{{ auth()->id() }}')
                                        .listen('.delivery.location.updated', (payload) => {
                                            if (payload.order_id !== {{ $order->id }}) return;

                                            emptyLabel?.classList.add('hidden');
                                            if (marker) {
                                                marker.setLatLng([payload.lat, payload.lng]);
                                            } else {
                                                marker = L.marker([payload.lat, payload.lng]).addTo(map);
                                            }
                                            map.panTo([payload.lat, payload.lng]);
                                        });
                                }
                            })();
                        </script>
                    @endpush
                @endif
            @endif
        </div>
    </div>
</x-layouts.app>
