<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptController extends Controller
{
    /** Available once there's actually something to receipt — paid, or a completed cash order. */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        abort_unless(
            $order->isPaid() || in_array($order->status->value, ['delivered', 'completed'], true),
            404,
            'A receipt is available once this order is paid or completed.'
        );

        $order->load('items', 'customer');

        $pdf = Pdf::loadView('pdf.receipt', compact('order'))->setPaper('a4');

        return $pdf->download('receipt-'.$order->order_number.'.pdf');
    }
}
