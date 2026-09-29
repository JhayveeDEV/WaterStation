<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Sandbox-only stand-in for a real GCash integration (e.g. via PayMongo or
 * Xendit). It never talks to a real bank — it just walks the customer through
 * a simulated checkout page so the whole "pay online" flow can be built,
 * demoed and tested end to end.
 *
 * To go live: implement PaymentGatewayContract against your provider's SDK
 * (create a real checkout session, verify their webhook signature, mark the
 * Payment paid from the webhook instead of PaymentMockController::complete),
 * then swap the binding in AppServiceProvider::register(). Nothing else in
 * the app — checkout, the order page, notifications — needs to change.
 */
class MockGcashGateway implements PaymentGatewayContract
{
    public function createCheckout(Order $order): Payment
    {
        return Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => 'gcash',
                'reference' => 'MOCK-'.strtoupper(Str::random(10)),
                'amount' => $order->total_amount,
                'status' => 'pending',
            ]
        );
    }
}
