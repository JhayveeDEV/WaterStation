<x-layouts.app title="Refunds">
    <div class="mb-8">
        <div class="mb-3 flex items-center gap-2">
            <h3 class="text-sm font-semibold text-gray-900">Awaiting refund</h3>
            @if ($pending->count())<span class="badge-warn">{{ $pending->count() }}</span>@endif
        </div>
        <div class="card divide-y divide-gray-100">
            @forelse ($pending as $payment)
                <div class="flex flex-wrap items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $payment->order->order_number }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->order->customer->name }}, cancelled {{ $payment->order->updated_at->diffForHumans() }}</p>
                        @if ($payment->order->cancel_reason)
                            <p class="mt-1 text-xs text-gray-400">Reason: {{ $payment->order->cancel_reason }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-4">
                        <p class="num text-base font-semibold text-gray-900">₱{{ number_format($payment->amount, 2) }}</p>
                        <form method="POST" action="{{ route('admin.refunds.mark-refunded', $payment) }}"
                              onsubmit="return confirm('Confirm you have sent ₱{{ number_format($payment->amount, 2) }} back to this customer through the original payment method?')">
                            @csrf
                            <button class="btn-primary btn-sm">Mark as refunded</button>
                        </form>
                    </div>
                </div>
            @empty
                <x-empty-state title="No refunds pending" description="Cancelled orders that were already paid online will show up here." />
            @endforelse
        </div>
    </div>

    <div>
        <h3 class="mb-3 text-sm font-semibold text-gray-900">Recently refunded</h3>
        <div class="card divide-y divide-gray-100">
            @forelse ($recent as $payment)
                <div class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <p class="text-sm text-gray-900">{{ $payment->order->order_number }}</p>
                        <p class="text-xs text-gray-500">{{ $payment->order->customer->name }}, refunded {{ $payment->refunded_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="badge-success badge-dot">Refunded</span>
                        <p class="num text-sm font-semibold text-gray-900">₱{{ number_format($payment->amount, 2) }}</p>
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-gray-400">No refunds processed yet.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
