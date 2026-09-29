<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        $stats = [
            'total_users' => User::count(),
            'total_customers' => User::role('customer')->count(),
            'orders_this_month' => Order::where('created_at', '>=', $startOfMonth)->count(),
            'revenue_this_month' => Order::where('payment_status', 'paid')->where('paid_at', '>=', $startOfMonth)->sum('total_amount'),
        ];

        $ordersByStatus = Order::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $revenueByDay = Order::where('payment_status', 'paid')
            ->where('paid_at', '>=', Carbon::now()->subDays(13))
            ->selectRaw('DATE(paid_at) as day, SUM(total_amount) as total')
            ->groupBy('day')->orderBy('day')->get();

        return view('admin.dashboard', compact('stats', 'ordersByStatus', 'revenueByDay'));
    }
}
