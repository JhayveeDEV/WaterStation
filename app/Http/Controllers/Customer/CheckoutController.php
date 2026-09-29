<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\DeliveryArea;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function create(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        abort_if(empty($cart), 302, redirect()->route('customer.cart')->with('status', 'Your cart is empty.'));

        $products = Product::whereIn('id', array_keys($cart))->with('inventory')->get();
        $addresses = $request->user()->addresses()->with('deliveryArea')->get();

        $lines = $products->map(fn ($p) => ['product' => $p, 'quantity' => $cart[$p->id], 'subtotal' => $p->price * $cart[$p->id]]);
        $subtotal = $lines->sum('subtotal');

        $checkoutToken = $request->session()->get('checkout_token') ?? (string) Str::uuid();
        $request->session()->put('checkout_token', $checkoutToken);

        return view('customer.checkout', compact('lines', 'subtotal', 'addresses', 'checkoutToken'));
    }

    public function store(CheckoutRequest $request, OrderService $orders)
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('customer.cart')->with('status', 'Your cart is empty.');
        }

        $lines = collect($cart)->map(fn ($qty, $productId) => ['product_id' => $productId, 'quantity' => $qty])->values()->all();

        try {
            $order = $orders->place(
                customer: $request->user(),
                lines: $lines,
                deliveryMethod: $request->delivery_method,
                addressId: $request->address_id,
                phone: $request->contact_phone,
                scheduleDate: $request->schedule_date,
                slot: $request->schedule_slot,
                paymentMethod: $request->payment_method,
                notes: $request->notes,
                checkoutToken: $request->checkout_token,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $request->session()->forget(['cart', 'checkout_token']);

        if ($order->payment_method === 'gcash') {
            return redirect()->route('customer.payments.initiate', $order);
        }

        return redirect()->route('customer.orders.show', $order)->with('status', 'Order placed! We will confirm it shortly.');
    }
}
