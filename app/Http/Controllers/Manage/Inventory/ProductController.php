<?php

namespace App\Http\Controllers\Manage\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Support\ImageResizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('inventory')->orderBy('name')->get();

        return view('manage.inventory.index', compact('products'));
    }

    public function create()
    {
        return view('manage.inventory.create');
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request);
        }

        $product = Product::create([...$data, 'is_active' => $request->boolean('is_active', true)]);
        $product->inventory()->create([
            'quantity_on_hand' => $data['quantity_on_hand'],
            'reorder_level' => $data['reorder_level'],
        ]);

        return redirect()->route('manage.inventory.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        $product->load('inventory');

        return view('manage.inventory.edit', compact('product'));
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $this->storeImage($request);
        }

        $product->update([...$data, 'is_active' => $request->boolean('is_active', true)]);
        $product->inventory()->update([
            'quantity_on_hand' => $data['quantity_on_hand'],
            'reorder_level' => $data['reorder_level'],
        ]);

        return redirect()->route('manage.inventory.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return back()->with('status', 'Product deactivated.');
    }

    /** Resizes large photos down before storing — see ImageResizer for why and the fail-open behavior. */
    private function storeImage($request): string
    {
        $file = $request->file('image');
        $resized = ImageResizer::resize($file);

        if ($resized === null) {
            return $file->store('products', 'public');
        }

        $path = 'products/'.Str::random(20).'.jpg';
        Storage::disk('public')->put($path, $resized);

        return $path;
    }
}
