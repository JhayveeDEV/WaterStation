<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryArea;
use Illuminate\Http\Request;

class DeliveryAreaController extends Controller
{
    public function index()
    {
        $areas = DeliveryArea::orderBy('name')->get();

        return view('admin.areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'fee' => ['required', 'numeric', 'min:0'],
        ]);

        DeliveryArea::create([...$data, 'is_active' => true]);

        return back()->with('status', 'Delivery area added.');
    }

    public function update(Request $request, DeliveryArea $deliveryArea)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $deliveryArea->update([...$data, 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Delivery area updated.');
    }

    public function destroy(DeliveryArea $deliveryArea)
    {
        $deliveryArea->update(['is_active' => false]);

        return back()->with('status', 'Delivery area deactivated.');
    }
}
