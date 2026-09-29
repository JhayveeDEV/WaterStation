<?php

namespace App\Http\Controllers\Manage\Orders;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/** Live kanban-style order board: every non-final order, grouped by status. Updates via Echo, no polling. */
class OrderBoardController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('customer', 'items')
            ->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Rejected])
            ->orderBy('schedule_date')
            ->get()
            ->groupBy(fn (Order $o) => $o->status->value);

        $columns = [
            OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing,
            OrderStatus::ReadyForPickup, OrderStatus::Assigned, OrderStatus::OutForDelivery,
        ];

        return view('manage.orders.board', compact('orders', 'columns'));
    }

    public function history(Request $request)
    {
        $orders = Order::with('customer')
            ->whereIn('status', [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Rejected])
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest('updated_at')
            ->paginate(20);

        return view('manage.orders.history', compact('orders'));
    }
}
