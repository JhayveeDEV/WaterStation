<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RecurringOrder;
use Illuminate\Http\Request;

class RecurringOrderController extends Controller
{
    public function index(Request $request)
    {
        $recurringOrders = $request->user()->recurringOrders()->with('lastOrder')->latest()->paginate(15);

        return view('customer.recurring-orders.index', compact('recurringOrders'));
    }

    public function store(Request $request, Order $order)
    {
        $this->authorize('view', $order);
        abort_unless(in_array($order->status->value, ['delivered', 'completed'], true), 422);

        $data = $request->validate([
            'frequency' => ['required', 'in:weekly,biweekly,monthly'],
        ]);

        $order->loadMissing('items');
        $request->user()->recurringOrders()->create([
            'source_order_id' => $order->id,
            'delivery_method' => $order->delivery_method,
            'address_id' => $order->address_id,
            'contact_phone' => $order->contact_phone,
            'schedule_slot' => $order->schedule_slot,
            'frequency' => $data['frequency'],
            'next_order_date' => $this->nextDate($data['frequency']),
            'items' => $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
            ])->all(),
            'customer_notes' => $order->customer_notes,
            'is_active' => true,
        ]);

        return redirect()->route('customer.recurring.index')->with('status', 'Repeat order scheduled. Future orders will use cash on delivery.');
    }

    public function toggle(Request $request, RecurringOrder $recurringOrder)
    {
        $this->ensureOwner($request, $recurringOrder);
        $recurringOrder->update(['is_active' => ! $recurringOrder->is_active]);

        return back()->with('status', $recurringOrder->is_active ? 'Repeat order resumed.' : 'Repeat order paused.');
    }

    public function skip(Request $request, RecurringOrder $recurringOrder)
    {
        $this->ensureOwner($request, $recurringOrder);
        abort_unless($recurringOrder->is_active, 422);

        $recurringOrder->update(['next_order_date' => $recurringOrder->nextDateAfter(today()), 'last_error' => null]);

        return back()->with('status', 'Next repeat order skipped.');
    }

    private function ensureOwner(Request $request, RecurringOrder $recurringOrder): void
    {
        abort_unless($recurringOrder->customer_id === $request->user()->id, 403);
    }

    private function nextDate(string $frequency): string
    {
        return match ($frequency) {
            'biweekly' => today()->addWeeks(2)->toDateString(),
            'monthly' => today()->addMonthNoOverflow()->toDateString(),
            default => today()->addWeek()->toDateString(),
        };
    }
}
