<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Services\NotificationService;

/**
 * The sandbox QR Ph checkout screen itself — see MockGcashGateway for why
 * this exists instead of a real redirect to PayMongo.
 */
class PaymentMockController extends Controller
{
    public function show(Payment $payment)
    {
        abort_unless(in_array($payment->provider, ['gcash', 'qrph'], true), 404);
        $this->authorize('view', $payment->order);

        return view('payments.mock', ['payment' => $payment->load('order')]);
    }

    public function complete(Payment $payment, NotificationService $notifications)
    {
        abort_unless(in_array($payment->provider, ['gcash', 'qrph'], true), 404);
        $this->authorize('view', $payment->order);

        if ($payment->status !== 'paid') {
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            $payment->order->update(['payment_status' => 'paid', 'paid_at' => now()]);

            ActivityLog::record(
                'payments',
                'paid',
                $payment->order_id,
                $payment->order->order_number.' via '.$payment->order->payment_method_label.' (sandbox)',
            );
            event(new PaymentStatusUpdated($payment->order->fresh()));

            $notifications->send(
                $payment->order->customer,
                'Payment received',
                'We received your '.$payment->order->payment_method_label.' payment for order '.$payment->order->order_number.'.',
                'ok',
                route('customer.orders.show', $payment->order),
                "Payment received for order {$payment->order->order_number} (₱".number_format($payment->amount, 2).' via '.$payment->order->payment_method_label.'). Thank you!'
            );
        }

        return redirect()->route('customer.orders.show', $payment->order)->with('status', 'Payment received — thank you!');
    }

    public function cancel(Payment $payment)
    {
        abort_unless(in_array($payment->provider, ['gcash', 'qrph'], true), 404);
        $this->authorize('view', $payment->order);

        if ($payment->status === 'pending') {
            $payment->update(['status' => 'cancelled']);
        }

        return redirect()->route('customer.orders.show', $payment->order)
            ->with('error', 'Payment was cancelled. You can try paying again anytime from this order.');
    }
}
