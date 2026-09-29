<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;

/**
 * Any online payment provider (GCash via PayMongo/Xendit, Maya, a card
 * processor, etc.) plugs in by implementing this one method. Swap the binding
 * in AppServiceProvider — nothing else in the app needs to change.
 */
interface PaymentGatewayContract
{
    /** Creates (or reuses) a pending payment intent for this order and returns it. */
    public function createCheckout(Order $order): Payment;
}
