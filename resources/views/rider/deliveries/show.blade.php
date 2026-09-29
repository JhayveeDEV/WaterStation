<x-layouts.app title="Delivery {{ $order->order_number }}">
    <div class="mx-auto max-w-xl space-y-6">
        <div class="card">
            <div class="card-header">
                <p class="text-sm font-semibold text-gray-900">{{ $order->order_number }}</p>
                <x-status-badge :status="$order->status" />
            </div>
            <div class="p-5">
                <p class="text-base font-medium leading-snug text-gray-900">{{ $order->delivery_address }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $order->schedule_label }}</p>

                <div class="mt-5 space-y-2 border-t border-gray-100 pt-4">
                    @foreach ($order->items as $item)
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-600">{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                            <span class="num text-gray-900">₱{{ number_format($item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                @if ($order->payment_method === 'cash')
                    <div class="mt-4 flex items-center justify-between rounded-md border border-amber-200 bg-amber-50 px-4 py-3">
                        <span class="text-sm font-medium text-amber-800">Collect in cash</span>
                        <span class="num text-lg font-semibold text-amber-900">₱{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                @else
                    <div class="mt-4 flex items-center justify-between rounded-md border border-brand-200 bg-brand-50 px-4 py-3">
                        <span class="text-sm font-medium text-brand-800">Paid via GCash</span>
                        <span class="text-sm text-brand-700">Nothing to collect</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Customer contact: shown only on the rider's own delivery, for reaching this customer if delivery hits a snag. --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Customer</h3></div>
            <div class="p-5">
                <div class="flex items-center gap-3">
                    <span class="avatar h-10 w-10 text-sm">{{ strtoupper(mb_substr($order->customer->name, 0, 1)) }}</span>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $order->customer->name }}</p>
                        <p class="num text-xs text-gray-500">{{ $order->contact_phone }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <a href="tel:{{ $order->contact_phone }}" class="btn-primary flex-1 py-2.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 0 1 2-2h2.28a1 1 0 0 1 .98.79l1 4a1 1 0 0 1-.27.95L7.3 10.4a12.05 12.05 0 0 0 5.3 5.3l1.66-1.68a1 1 0 0 1 .95-.27l4 1a1 1 0 0 1 .79.98V19a2 2 0 0 1-2 2h-1C9.16 21 3 14.84 3 7V5Z"/></svg>
                        Call
                    </a>
                    @if ($order->customer->email)
                        <a href="mailto:{{ $order->customer->email }}" class="btn-secondary flex-1 py-2.5">Email</a>
                    @endif
                </div>
                @if ($order->customer_notes)
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3">
                        <p class="text-xs font-medium text-amber-800">Note from customer</p>
                        <p class="mt-0.5 text-sm text-amber-900">{{ $order->customer_notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($order->isDelivery())
            <div class="card p-4">
                <div class="mb-3 flex items-center justify-between px-1 pt-1">
                    <h3 class="card-title">Your location and customer</h3>
                    <a href="{{ $order->hasLocationPin()
                            ? 'https://www.google.com/maps/dir/?api=1&destination='.$order->delivery_lat.','.$order->delivery_lng
                            : 'https://www.google.com/maps/search/?api=1&query='.urlencode($order->delivery_address) }}"
                       target="_blank" rel="noopener" class="btn-secondary btn-sm">Get directions</a>
                </div>

                <div id="rider-map" class="isolate h-56 rounded-md bg-gray-100"
                     data-lat="{{ $order->delivery_lat ?? '' }}" data-lng="{{ $order->delivery_lng ?? '' }}"></div>
                <p id="rider-proximity" class="px-1 pb-1 pt-2 text-xs text-gray-500">
                    @if ($order->hasLocationPin())
                        Turn on location sharing to see how far you are from the customer.
                    @else
                        This customer has no map pin, so distance cannot be calculated. Use the address or directions link above.
                    @endif
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

                        const destination = el.dataset.lat && el.dataset.lng
                            ? [Number(el.dataset.lat), Number(el.dataset.lng)]
                            : null;
                        const proximity = document.getElementById('rider-proximity');
                        const map = L.map(el, { zoomControl: false }).setView(destination || [7.0731, 125.6128], destination ? 15 : 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19,
                        }).addTo(map);

                        if (destination) {
                            L.marker(destination).addTo(map).bindPopup('Customer location');
                        }

                        let riderMarker = null;
                        let hasFittedBoth = false;

                        function updateRiderLocation(lat, lng) {
                            const riderLocation = [lat, lng];

                            if (riderMarker) {
                                riderMarker.setLatLng(riderLocation);
                            } else {
                                riderMarker = L.circleMarker(riderLocation, {
                                    radius: 9,
                                    color: '#ffffff',
                                    weight: 3,
                                    fillColor: '#0f766e',
                                    fillOpacity: 1,
                                }).addTo(map).bindPopup('Your current location');
                            }

                            if (!hasFittedBoth) {
                                const points = destination ? [destination, riderLocation] : [riderLocation];
                                map.fitBounds(L.latLngBounds(points).pad(0.2), { maxZoom: 15 });
                                hasFittedBoth = true;
                            }

                            if (!destination) return;

                            const toRadians = (degrees) => degrees * Math.PI / 180;
                            const latDelta = toRadians(destination[0] - lat);
                            const lngDelta = toRadians(destination[1] - lng);
                            const a = Math.sin(latDelta / 2) ** 2
                                + Math.cos(toRadians(lat)) * Math.cos(toRadians(destination[0]))
                                * Math.sin(lngDelta / 2) ** 2;
                            const meters = 6371000 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                            const distance = meters < 1000
                                ? `${Math.round(meters)} m`
                                : `${(meters / 1000).toFixed(1)} km`;

                            proximity.textContent = meters <= 500
                                ? `You are near the customer, about ${distance} away in a straight line.`
                                : `About ${distance} from the customer in a straight line.`;
                        }

                        window.addEventListener('rider-location-updated', (event) => {
                            updateRiderLocation(event.detail.lat, event.detail.lng);
                        });
                    })();
                </script>
            @endpush
        @endif

        @if (auth()->user()->hasPermissionTo('update deliveries'))
        @if ($order->status->value === 'out_for_delivery')
            <div class="card p-4" data-live-location data-location-url="{{ route('rider.deliveries.location', $order) }}">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">Share my location</p>
                        <p class="text-xs text-gray-500" data-location-status>Off — the customer can't see your position yet.</p>
                    </div>
                    <button type="button" class="btn-secondary shrink-0" data-location-toggle>Turn on</button>
                </div>
            </div>
        @endif

        @if (in_array($order->status->value, ['assigned', 'out_for_delivery']))
            <div>
                @if ($order->status->value === 'assigned')
                    <form method="POST" action="{{ route('rider.deliveries.dispatch', $order) }}">
                        @csrf
                        <button class="btn-primary w-full py-3 text-base">Start delivery</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('rider.deliveries.complete', $order) }}"
                          onsubmit="return confirm('Confirm this order has been delivered?')">
                        @csrf
                        <button class="btn-primary w-full py-3 text-base">Mark as delivered</button>
                    </form>
                @endif
            </div>
        @endif
        @endif
    </div>

    @if (auth()->user()->hasPermissionTo('update deliveries'))
    <script>
        (function () {
            const box = document.querySelector('[data-live-location]');
            if (!box) return;

            const url = box.dataset.locationUrl;
            const statusEl = box.querySelector('[data-location-status]');
            const toggleBtn = box.querySelector('[data-location-toggle]');
            let watchId = null;
            let lastSentAt = 0;

            function send(lat, lng) {
                const now = Date.now();
                if (now - lastSentAt < 5000) return; // at most once every 5s, regardless of GPS chatter
                lastSentAt = now;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ lat, lng }),
                }).catch(() => {});
            }

            toggleBtn.addEventListener('click', () => {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                    toggleBtn.textContent = 'Turn on';
                    statusEl.textContent = "Off — the customer can't see your position yet.";
                    return;
                }

                if (!navigator.geolocation) {
                    statusEl.textContent = 'Location isn\'t available on this device.';
                    return;
                }

                statusEl.textContent = 'Requesting location permission…';
                watchId = navigator.geolocation.watchPosition(
                    (pos) => {
                        window.dispatchEvent(new CustomEvent('rider-location-updated', {
                            detail: { lat: pos.coords.latitude, lng: pos.coords.longitude },
                        }));
                        send(pos.coords.latitude, pos.coords.longitude);
                        statusEl.textContent = 'On — the customer can see your position live.';
                        toggleBtn.textContent = 'Turn off';
                    },
                    () => { statusEl.textContent = 'Location permission was denied.'; },
                    { enableHighAccuracy: true, maximumAge: 8000, timeout: 15000 }
                );
            });
        })();
    </script>
    @endif
</x-layouts.app>
