<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PayMongoPaymentConfirmer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->latest()->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order, PayMongoPaymentConfirmer $payments)
    {
        $this->authorize('view', $order);
        $order->load('payment');

        if ($order->payment?->provider === 'paymongo'
            && ! $order->isPaid()) {
            try {
                $payments->confirmFromCheckoutSession($order->payment);
            } catch (ConnectionException|RequestException|PaymentGatewayException $exception) {
                Log::warning('Unable to verify PayMongo payment after customer return.', [
                    'order_id' => $order->id,
                    'payment_id' => $order->payment->id,
                    'exception' => $exception,
                ]);
            }
        }

        $order->refresh()->load('items.product', 'histories.user', 'delivery.rider', 'payment');

        return view('customer.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order, OrderService $orders)
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        if (! $request->user()->can('cancel', $order)) {
            return back()->with('error', 'This order can no longer be cancelled — it may have already been confirmed or paid. Please contact us if you need to change it.');
        }

        $orders->transition($order, OrderStatus::Cancelled, $request->user(), 'Cancelled by customer');

        return back()->with('status', 'Order cancelled.');
    }

    /** Repopulates the cart from a past order. Silently skips items that are no longer sold or out of stock. */
    public function reorder(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        $order->load('items.product.inventory');

        $cart = $request->session()->get('cart', []);
        $skipped = [];

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active) {
                $skipped[] = $item->product_name;

                continue;
            }

            $available = $product->inventory?->quantity_on_hand ?? 0;

            if ($available < 1) {
                $skipped[] = $product->name;

                continue;
            }

            $cart[$product->id] = min($available, ($cart[$product->id] ?? 0) + $item->quantity);
        }

        $request->session()->put('cart', $cart);

        if (empty($cart)) {
            return redirect()->route('customer.shop')->with('error', 'None of the items from that order are available right now.');
        }

        $message = 'Added to your cart.';
        if (! empty($skipped)) {
            $message .= ' Not available right now: '.implode(', ', array_unique($skipped)).'.';
        }

        return redirect()->route('customer.cart')->with('status', $message);
    }
}
