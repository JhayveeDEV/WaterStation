<x-layouts.guest title="Pay with GCash">
    <div role="note" class="mb-5 flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v5M12 16.5v.01M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
        <p>Sandbox mode. No real money moves here.</p>
    </div>

    <dl class="mb-5 rounded-lg border border-gray-200 text-sm">
        <div class="flex justify-between gap-4 px-4 py-3">
            <dt class="text-gray-500">Order</dt>
            <dd class="font-medium text-gray-900">{{ $payment->order->order_number }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-t border-gray-100 px-4 py-3">
            <dt class="text-gray-500">Reference</dt>
            <dd class="break-all text-right font-mono text-xs text-gray-500">{{ $payment->reference }}</dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 rounded-b-lg border-t border-gray-100 bg-gray-50 px-4 py-3">
            <dt class="font-medium text-gray-700">Amount to pay</dt>
            <dd class="num text-xl font-semibold text-gray-900">₱{{ number_format($payment->amount, 2) }}</dd>
        </div>
    </dl>

    <p class="mb-3 text-xs text-gray-500">Choose an outcome to test the payment flow.</p>

    <form method="POST" action="{{ route('payments.mock.complete', $payment) }}">
        @csrf
        <button class="btn-primary w-full py-2.5">Simulate successful payment</button>
    </form>
    <form method="POST" action="{{ route('payments.mock.cancel', $payment) }}" class="mt-2">
        @csrf
        <button class="btn-secondary w-full py-2.5">Cancel and go back</button>
    </form>
</x-layouts.guest>