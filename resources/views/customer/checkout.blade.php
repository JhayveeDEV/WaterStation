<x-layouts.app title="Checkout">
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('customer.checkout.store') }}" class="space-y-6 lg:col-span-2"
              x-data="{ method: '{{ old('delivery_method', 'delivery') }}', payment: '{{ old('payment_method', 'cash') }}', submitting: false }"
              @submit="submitting = true">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="card">
                <div class="card-header"><h3 class="card-title">Delivery method</h3></div>
                <div class="p-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="check-tile cursor-pointer py-3">
                            <input type="radio" name="delivery_method" value="delivery" x-model="method" {{ old('delivery_method', 'delivery') === 'delivery' ? 'checked' : '' }}>
                            <span class="font-medium">Delivery</span>
                        </label>
                        <label class="check-tile cursor-pointer py-3">
                            <input type="radio" name="delivery_method" value="pickup" x-model="method" {{ old('delivery_method') === 'pickup' ? 'checked' : '' }}>
                            <span class="font-medium">Pickup at station</span>
                        </label>
                    </div>

                    <div class="mt-4" x-show="method === 'delivery'">
                        <label class="label" for="address_id">Delivery address</label>
                        <select id="address_id" name="address_id" class="input {{ $errors->has('address_id') ? 'input-error' : '' }}">
                            <option value="">Select address</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address->id }}" {{ (string) old('address_id') === (string) $address->id ? 'selected' : '' }}>{{ $address->label ?: 'Address' }}: {{ $address->fullText() }}</option>
                            @endforeach
                        </select>
                        @error('address_id') <p class="field-error">{{ $message }}</p> @enderror
                        @if ($addresses->isEmpty())
                            <p class="mt-1.5 text-xs text-amber-600">
                                You have no saved address yet. <a href="{{ route('customer.addresses.index') }}" class="font-medium underline">Add one first</a>.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Contact &amp; schedule</h3></div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="label" for="contact_phone">Contact phone</label>
                        <input id="contact_phone" type="tel" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}" autocomplete="tel"
                               class="input {{ $errors->has('contact_phone') ? 'input-error' : '' }}" required>
                        @error('contact_phone') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="schedule_date">Date</label>
                            <input id="schedule_date" type="date" name="schedule_date" min="{{ now()->toDateString() }}" value="{{ old('schedule_date', now()->toDateString()) }}"
                                   class="input {{ $errors->has('schedule_date') ? 'input-error' : '' }}" required>
                            @error('schedule_date') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="schedule_slot">Time slot</label>
                            <select id="schedule_slot" name="schedule_slot" class="input">
                                @foreach (['08:00-10:00' => '8:00 AM – 10:00 AM', '10:00-12:00' => '10:00 AM – 12:00 PM', '13:00-15:00' => '1:00 PM – 3:00 PM', '15:00-17:00' => '3:00 PM – 5:00 PM'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('schedule_slot') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="notes">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Gate code, landmark, who to ask for" class="input">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Payment method</h3></div>
                <div class="p-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="check-tile cursor-pointer py-3">
                            <input type="radio" name="payment_method" value="cash" x-model="payment" {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                            <span class="font-medium">Cash on delivery or pickup</span>
                        </label>
                        <label class="check-tile cursor-pointer py-3">
                            <input type="radio" name="payment_method" value="gcash" x-model="payment" {{ old('payment_method') === 'gcash' ? 'checked' : '' }}>
                            <span class="font-medium">GCash</span>
                        </label>
                    </div>
                    <p class="mt-3 text-xs text-gray-500" x-show="payment === 'gcash'" x-cloak>
                        You'll be taken to a secure GCash checkout to pay before the order is confirmed.
                    </p>
                    @error('payment_method') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button class="btn-primary w-full py-3" :disabled="submitting">
                <span x-show="!submitting" x-text="payment === 'gcash' ? 'Continue to GCash payment' : 'Place order'">Place order</span>
                <span x-show="submitting" x-cloak>Placing your order…</span>
            </button>
        </form>

        <div class="card p-5 lg:sticky lg:top-20">
            <h3 class="card-title mb-4">Order summary</h3>
            <div class="space-y-2.5">
                @foreach ($lines as $line)
                    <div class="flex justify-between gap-3 text-sm">
                        <span class="text-gray-600">{{ $line['product']->name }} <span class="text-gray-400">× {{ $line['quantity'] }}</span></span>
                        <span class="num text-gray-900">₱{{ number_format($line['subtotal'], 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between border-t border-gray-100 pt-4 text-sm font-semibold">
                <span>Subtotal</span>
                <span class="num">₱{{ number_format($subtotal, 2) }}</span>
            </div>
            <p class="mt-1.5 text-xs text-gray-400">Delivery fee is added based on the address you choose.</p>
        </div>
    </div>
</x-layouts.app>
