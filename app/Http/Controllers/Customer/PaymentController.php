<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayContract;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Request $request, Order $order, PaymentGatewayContract $gateway)
    {
        $this->authorize('view', $order);

        if ($order->payment_method !== 'gcash') {
            return redirect()->route('customer.orders.show', $order);
        }

        if ($order->isPaid()) {
            return redirect()->route('customer.orders.show', $order)->with('status', 'This order is already paid.');
        }

        $payment = $gateway->createCheckout($order);

        return redirect()->route('payments.mock.show', $payment);
    }
}
