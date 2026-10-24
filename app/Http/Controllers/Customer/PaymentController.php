<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayContract;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function initiate(Request $request, Order $order, PaymentGatewayContract $gateway)
    {
        $this->authorize('view', $order);

        if (! $order->usesOnlinePayment()) {
            return redirect()->route('customer.orders.show', $order);
        }

        if ($order->isPaid()) {
            return redirect()->route('customer.orders.show', $order)->with('status', 'This order is already paid.');
        }

        try {
            $payment = $gateway->createCheckout($order);
        } catch (ConnectionException|RequestException|PaymentGatewayException $exception) {
            Log::error('Unable to start online payment checkout.', [
                'order_id' => $order->id,
                'exception' => $exception,
            ]);

            return redirect()->route('customer.orders.show', $order)
                ->with('error', 'We could not start your '.$order->payment_method_label.' payment. Please try again shortly.');
        }

        if ($payment->provider === 'paymongo' && $payment->checkout_url) {
            return redirect()->away($payment->checkout_url);
        }

        return redirect()->route('payments.mock.show', $payment);
    }
}
