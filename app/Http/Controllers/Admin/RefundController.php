<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Payment;
use App\Services\NotificationService;

class RefundController extends Controller
{
    public function index()
    {
        $pending = Payment::with('order.customer')->where('refund_status', 'pending')->latest()->get();
        $recent = Payment::with('order.customer')->where('refund_status', 'refunded')->latest('refunded_at')->limit(20)->get();

        return view('admin.refunds.index', compact('pending', 'recent'));
    }

    /** Admin confirms the refund was actually sent back via GCash — this app has no live refund gateway, so it's a manual step. */
    public function markRefunded(Payment $payment, NotificationService $notifications)
    {
        abort_unless($payment->refund_status === 'pending', 422, 'This payment is not awaiting a refund.');

        $payment->update(['refund_status' => 'refunded', 'refunded_at' => now()]);

        ActivityLog::record('payments', 'refunded', $payment->order_id, $payment->order->order_number.' — ₱'.number_format($payment->amount, 2));

        $notifications->send(
            $payment->order->customer,
            'Refund processed',
            'Your refund for order '.$payment->order->order_number.' has been sent.',
            'ok',
            null,
            "Your refund of ₱".number_format($payment->amount, 2)." for order {$payment->order->order_number} has been sent to your GCash."
        );

        return back()->with('status', 'Marked as refunded.');
    }
}
