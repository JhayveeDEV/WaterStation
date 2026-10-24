<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Development stand-in for a real QR Ph checkout. It never contacts a bank;
 * set PAYMENTS_GATEWAY=paymongo to use the real PayMongo hosted checkout.
 */
class MockGcashGateway implements PaymentGatewayContract
{
    public function createCheckout(Order $order): Payment
    {
        return Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'provider' => $order->payment_method === 'gcash' ? 'gcash' : 'qrph',
                'reference' => 'MOCK-'.strtoupper(Str::random(10)),
                'amount' => $order->total_amount,
                'status' => 'pending',
            ]
        );
    }
}
