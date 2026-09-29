<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\DeliveryArea;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->with('deliveryArea')->latest()->get();
        $areas = DeliveryArea::where('is_active', true)->orderBy('name')->get();

        return view('customer.addresses', compact('addresses', 'areas'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'delivery_area_id' => ['required', 'exists:delivery_areas,id'],
            'address_text' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $request->user()->addresses()->create($data);

        return back()->with('status', 'Address saved.');
    }

    public function destroy(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return back()->with('status', 'Address removed.');
    }
}
