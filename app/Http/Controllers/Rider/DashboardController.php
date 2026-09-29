<?php

namespace App\Http\Controllers\Rider;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $rider = $request->user();

        $active = $rider->deliveries()->with('order.customer')
            ->whereHas('order', fn ($q) => $q->whereIn('status', [OrderStatus::Assigned, OrderStatus::OutForDelivery]))
            ->get();

        $completedToday = $rider->deliveries()->whereDate('delivered_at', today())->count();

        return view('rider.dashboard', compact('active', 'completedToday', 'rider'));
    }

    public function toggleAvailability(Request $request)
    {
        $request->user()->update(['is_available' => ! $request->user()->is_available]);

        return back();
    }
}
