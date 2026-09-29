<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/** Cart lives in the session; nothing to persist until checkout. */
class CartController extends Controller
{
    public function index(Request $request)
    {
        return view('customer.cart', ['lines' => $this->cartWithProducts($request)]);
    }

    public function add(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);

        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $data['quantity'];
        $request->session()->put('cart', $cart);

        return back()->with('status', $product->name.' added to cart.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);

        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = $data['quantity'];
        $request->session()->put('cart', $cart);

        return back();
    }

    public function remove(Request $request, Product $product)
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);

        return back()->with('status', 'Item removed.');
    }

    /** @return array<int, array{product: Product, quantity:int, subtotal: float}> */
    private function cartWithProducts(Request $request): array
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return [];
        }

        $products = Product::whereIn('id', array_keys($cart))->with('inventory')->get()->keyBy('id');

        $lines = [];
        foreach ($cart as $productId => $qty) {
            if (! $product = $products->get($productId)) {
                continue;
            }
            $lines[] = ['product' => $product, 'quantity' => $qty, 'subtotal' => $product->price * $qty];
        }

        return $lines;
    }
}
