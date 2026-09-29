<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ShopController extends Controller
{
    public function index()
    {
        $products = Product::active()->with('inventory')->orderBy('name')->get();

        return view('customer.shop', compact('products'));
    }
}
