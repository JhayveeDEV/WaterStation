<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\InventoryMovement;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        [$from, $to, $orders] = $this->paidOrdersInRange($request);

        $summary = [
            'orders' => $orders->count(),
            'revenue' => $orders->sum('total_amount'),
            'average' => $orders->count() ? round($orders->avg('total_amount'), 2) : 0,
        ];

        $productSales = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as units_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $outstanding = Order::whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('payment_status', 'unpaid')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as amount')
            ->first();

        $deliveries = Delivery::whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get(['assigned_at', 'delivered_at']);
        $durations = $deliveries->filter(fn ($delivery) => $delivery->assigned_at)
            ->map(fn ($delivery) => $delivery->assigned_at->diffInMinutes($delivery->delivered_at));

        $summary['outstanding_orders'] = (int) $outstanding->orders;
        $summary['outstanding_amount'] = (float) $outstanding->amount;
        $summary['delivered'] = $deliveries->count();
        $summary['average_delivery_minutes'] = $durations->isNotEmpty() ? (int) round($durations->avg()) : null;
        $exportRoute = $request->user()->hasRole('admin') ? 'admin.reports.sales.export' : 'manage.reports.sales.export';

        return view('admin.reports.sales', compact('orders', 'summary', 'productSales', 'from', 'to', 'exportRoute'));
    }

    public function salesExport(Request $request): StreamedResponse
    {
        [$from, $to, $orders] = $this->paidOrdersInRange($request);

        $filename = 'sales-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order number', 'Customer', 'Date', 'Method', 'Payment method', 'Total (PHP)']);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->customer->name,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->method_label,
                    ucfirst($order->payment_method),
                    number_format((float) $order->total_amount, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function inventory()
    {
        $movements = InventoryMovement::with('product', 'user')->latest()->paginate(30);

        return view('admin.reports.inventory', compact('movements'));
    }

    /** @return array{0: Carbon, 1: Carbon, 2: Collection} */
    private function paidOrdersInRange(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($data['to'] ?? now()->toDateString());

        if ($to->lt($from)) {
            $to = $from->copy();
        }

        $orders = Order::with('customer')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('payment_status', 'paid')
            ->latest()->get();

        return [$from, $to, $orders];
    }
}
