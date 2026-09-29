<x-layouts.app title="Addresses">
    <div class="grid items-start gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add a new address</h3></div>
            <form method="POST" action="{{ route('customer.addresses.store') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="label" for="addr-label">Label</label>
                    <input id="addr-label" name="label" value="{{ old('label') }}" placeholder="Home, Office…" class="input">
                </div>
                <div>
                    <label class="label" for="addr-area">Delivery area</label>
                    <select id="addr-area" name="delivery_area_id" class="input {{ $errors->has('delivery_area_id') ? 'input-error' : '' }}" required>
                        <option value="">Select area</option>
                        @foreach ($areas as $area)
                            <option value="{{ $area->id }}" {{ (string) old('delivery_area_id') === (string) $area->id ? 'selected' : '' }}>{{ $area->name }} (₱{{ number_format($area->fee, 2) }} delivery)</option>
                        @endforeach
                    </select>
                    @error('delivery_area_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="addr-text">Street address</label>
                    <textarea id="addr-text" name="address_text" rows="2" class="input {{ $errors->has('address_text') ? 'input-error' : '' }}" required>{{ old('address_text') }}</textarea>
                    @error('address_text') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="addr-city">City</label>
                    <input id="addr-city" name="city" value="{{ old('city') }}" class="input">
                </div>
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <label class="label mb-0">
                            Pin your exact location
                            <span class="font-normal text-gray-400">(recommended)</span>
                        </label>
                        <button type="button" id="use-my-location" class="btn-secondary btn-sm shrink-0">Use my location</button>
                    </div>
                    <div id="pin-map" class="isolate h-56 rounded-md border border-gray-200 bg-gray-100"></div>
                    <p class="mt-1.5 text-xs text-gray-500" id="pin-status">Tap the map to drop a pin, or drag it to adjust.</p>
                    <input type="hidden" name="lat" id="pin-lat">
                    <input type="hidden" name="lng" id="pin-lng">
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="is_default" value="1">
                    Set as default address
                </label>
                <button class="btn-primary w-full">Save address</button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse ($addresses as $address)
                <div class="card flex items-start justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 text-sm font-medium text-gray-900">
                            {{ $address->label ?: 'Address' }}
                            @if ($address->is_default) <span class="badge-muted">Default</span> @endif
                        </p>
                        <p class="mt-0.5 text-sm text-gray-500">{{ $address->fullText() }}</p>
                        @if ($address->isPinned())
                            <p class="mt-2"><span class="badge-success badge-dot">Location pinned</span></p>
                        @else
                            <p class="mt-2"><span class="badge-warn badge-dot">No pin set</span></p>
                            <p class="mt-1 text-xs text-gray-400">Riders will only have the text address to go by.</p>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('customer.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost btn-sm text-red-600 hover:bg-red-50">Remove</button>
                    </form>
                </div>
            @empty
                <x-empty-state card title="No saved addresses" description="Add one to enable delivery orders." />
            @endforelse
        </div>
    </div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            (function () {
                const el = document.getElementById('pin-map');
                if (!el || typeof L === 'undefined') return;

                const latInput = document.getElementById('pin-lat');
                const lngInput = document.getElementById('pin-lng');
                const status = document.getElementById('pin-status');
                const locationButton = document.getElementById('use-my-location');

                const defaultCenter = [7.0731, 125.6128]; // Davao City — a sane starting view
                const map = L.map(el).setView(defaultCenter, 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(map);

                let marker = null;

                function setPin(lat, lng) {
                    latInput.value = lat;
                    lngInput.value = lng;
                    status.textContent = 'Pin set. Drag it to fine-tune.';
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                        marker.on('dragend', () => {
                            const pos = marker.getLatLng();
                            setPin(pos.lat, pos.lng);
                        });
                    }
                }

                function useMyLocation() {
                    if (!navigator.geolocation) {
                        status.textContent = 'Location is not available on this device. Tap the map to pin manually.';
                        return;
                    }

                    locationButton.disabled = true;
                    locationButton.textContent = 'Finding you…';
                    status.textContent = 'Requesting your location permission…';
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            setPin(pos.coords.latitude, pos.coords.longitude);
                            map.setView([pos.coords.latitude, pos.coords.longitude], 17);
                            locationButton.disabled = false;
                            locationButton.textContent = 'Use my location';
                            status.textContent = 'Your current location is pinned. Drag the marker if needed.';
                        },
                        () => {
                            locationButton.disabled = false;
                            locationButton.textContent = 'Use my location';
                            status.textContent = 'Location permission was denied. You can still tap the map to pin manually.';
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                }

                map.on('click', (e) => setPin(e.latlng.lat, e.latlng.lng));
                locationButton?.addEventListener('click', useMyLocation);

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (pos) => map.setView([pos.coords.latitude, pos.coords.longitude], 15),
                        () => {} // silently keep the default view if permission is denied
                    );
                }
            })();
        </script>
    @endpush
</x-layouts.app>
