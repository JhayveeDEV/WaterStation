<?php

namespace App\Http\Controllers\Manage\Orders;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;

class OrderActionController extends Controller
{
    public function show(Order $order)
    {
        $order->load('items.product', 'customer', 'histories.user', 'delivery.rider');

        return view('manage.orders.show', ['order' => $order, 'riders' => User::role('rider')->where('status', 'active')->get()]);
    }

    public function transition(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(OrderStatus::values())],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $orders->transition($order, OrderStatus::from($data['status']), $request->user(), $data['note'] ?? null);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Order updated.');
    }

    public function assignRider(Request $request, Order $order, OrderService $orders)
    {
        $data = $request->validate(['rider_id' => ['required', 'exists:users,id']]);
        $rider = User::role('rider')->findOrFail($data['rider_id']);

        try {
            $orders->assignRider($order, $rider, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Rider assigned.');
    }
}
