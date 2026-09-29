<?php

namespace App\Http\Controllers\Rider;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use RuntimeException;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $deliveries = $request->user()->deliveries()->with('order.customer')->latest()->paginate(15);

        return view('rider.deliveries.index', compact('deliveries'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('handleDelivery', $order);
        $order->load('items.product', 'customer', 'address', 'delivery');

        return view('rider.deliveries.show', compact('order'));
    }

    public function dispatch(Request $request, Order $order, OrderService $orders)
    {
        $this->authorize('handleDelivery', $order);

        try {
            $orders->transition($order, OrderStatus::OutForDelivery, $request->user(), 'Rider picked up the order');
            $order->delivery()->update(['status' => 'out_for_delivery', 'dispatched_at' => now()]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Marked as out for delivery.');
    }

    public function complete(Request $request, Order $order, OrderService $orders)
    {
        $this->authorize('handleDelivery', $order);

        try {
            $orders->transition($order, OrderStatus::Delivered, $request->user(), 'Delivered by rider');
            $order->delivery()->update(['status' => 'delivered', 'delivered_at' => now()]);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Delivery completed.');
    }

    /** Rider's phone reports its position while the order is out for delivery. See OrderService for the broadcast. */
    public function updateLocation(Request $request, Order $order, OrderService $orders)
    {
        $this->authorize('handleDelivery', $order);

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        abort_unless($order->status->value === 'out_for_delivery', 422, 'Location sharing only applies while the order is out for delivery.');

        $orders->reportRiderLocation($order, $data['lat'], $data['lng']);

        return response()->json(['ok' => true]);
    }
}
