<?php

namespace App\Http\Controllers\Manage\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StockController extends Controller
{
    public function store(Request $request, Product $product, InventoryService $inventory)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['received', 'adjusted', 'damaged'])],
            'quantity' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'supplier_name' => ['nullable', 'string', 'max:120'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            match ($data['type']) {
                'received' => $inventory->receive(
                    $product,
                    $data['quantity'],
                    $data['reason'] ?? null,
                    $request->user()->id,
                    $data['supplier_name'] ?? null,
                    isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                ),
                'damaged' => $inventory->markDamaged($product, $data['quantity'], $data['reason'] ?? null, $request->user()->id),
                default => $inventory->adjust($product, $data['quantity'], $data['reason'] ?? null, $request->user()->id),
            };
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Stock updated.');
    }

    public function movements(Product $product)
    {
        $movements = $product->movements()->with('user')->latest()->paginate(25);

        return view('manage.inventory.movements', compact('product', 'movements'));
    }
}
