<?php

namespace App\Http\Controllers\Manage;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'pending' => Order::where('status', OrderStatus::Pending)->count(),
            'in_progress' => Order::whereIn('status', [
                OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Assigned, OrderStatus::OutForDelivery,
            ])->count(),
            'completed_today' => Order::where('status', OrderStatus::Completed)->whereDate('updated_at', $today)->count(),
            'revenue_today' => Order::where('payment_status', 'paid')->whereDate('paid_at', $today)->sum('total_amount'),
        ];

        $lowStock = Inventory::with('product')->whereColumn('quantity_on_hand', '<=', 'reorder_level')->get();

        $recentOrders = Order::with('customer')->latest()->limit(8)->get();

        return view('manage.dashboard', compact('stats', 'lowStock', 'recentOrders'));
    }
}
