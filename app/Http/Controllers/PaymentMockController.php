<?php

namespace App\Http\Controllers;

use App\Events\PaymentStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * The sandbox "GCash" checkout screen itself — see MockGcashGateway for why
 * this exists instead of a real redirect to GCash/PayMongo.
 */
class PaymentMockController extends Controller
{
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment->order);

        return view('payments.mock', ['payment' => $payment->load('order')]);
    }

    public function complete(Payment $payment, NotificationService $notifications)
    {
        $this->authorize('view', $payment->order);

        if ($payment->status !== 'paid') {
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            $payment->order->update(['payment_status' => 'paid', 'paid_at' => now()]);

            ActivityLog::record('payments', 'paid', $payment->order_id, $payment->order->order_number.' via GCash (sandbox)');
            event(new PaymentStatusUpdated($payment->order->fresh()));

            $notifications->send(
                $payment->order->customer,
                'Payment received',
                'We received your GCash payment for order '.$payment->order->order_number.'.',
                'ok',
                route('customer.orders.show', $payment->order),
                "Payment received for order {$payment->order->order_number} (₱".number_format($payment->amount, 2).' via GCash). Thank you!'
            );
        }

        return redirect()->route('customer.orders.show', $payment->order)->with('status', 'Payment received — thank you!');
    }

    public function cancel(Payment $payment)
    {
        $this->authorize('view', $payment->order);

        if ($payment->status === 'pending') {
            $payment->update(['status' => 'cancelled']);
        }

        return redirect()->route('customer.orders.show', $payment->order)
            ->with('error', 'Payment was cancelled. You can try paying again anytime from this order.');
    }
}
