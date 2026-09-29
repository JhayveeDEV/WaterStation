<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        @page { margin: 40px 44px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.45; color: #1f2937; margin: 0; }
        p { margin: 0 0 3px; }
        h1 { font-size: 20px; margin: 0 0 3px; color: #276a63; }
        .muted { color: #6b7280; }
        .small { font-size: 10px; }
        .amount { text-align: right; }

        .header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #276a63; }
        .header td { vertical-align: top; padding: 0 0 14px; }
        .header .right { text-align: right; }
        .doc-title { font-size: 14px; font-weight: bold; color: #111827; }

        .info { width: 100%; border-collapse: collapse; margin: 20px 0 6px; }
        .info td { width: 50%; vertical-align: top; padding: 0 12px 0 0; }
        .info .label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }

        .badge { display: inline-block; padding: 2px 9px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-paid { background: #f0f7f6; color: #276a63; }
        .badge-unpaid { background: #fffbeb; color: #b45309; }

        .items { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .items th { text-align: left; font-size: 10px; font-weight: normal; color: #6b7280; border-bottom: 1px solid #d1d5db; padding: 6px 0; }
        .items th.amount { text-align: right; }
        .items td { padding: 9px 0; border-bottom: 1px solid #f3f4f6; }

        .totals { width: 250px; margin-left: auto; border-collapse: collapse; margin-top: 12px; }
        .totals td { padding: 3px 0; }
        .totals .label { color: #6b7280; }
        .totals .grand td { font-weight: bold; font-size: 14px; color: #111827; border-top: 1px solid #276a63; padding-top: 8px; }

        .footer { margin-top: 36px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>{{ config('app.name') }}</h1>
                <p class="muted">Online water delivery &amp; refilling station</p>
            </td>
            <td class="right">
                <p class="doc-title">Receipt</p>
                <p class="muted">{{ $order->order_number }}</p>
                <p class="muted">{{ $order->created_at->format('M j, Y g:i A') }}</p>
            </td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td>
                <p class="label">Billed to</p>
                <p><strong>{{ $order->customer->name }}</strong></p>
                <p class="muted">{{ $order->contact_phone }}</p>
            </td>
            <td>
                <p class="label">{{ $order->method_label }}</p>
                @if ($order->isDelivery())
                    <p>{{ $order->delivery_address }}</p>
                @else
                    <p class="muted">Picked up at the station</p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin-top: 10px;">
        <span class="badge {{ $order->isPaid() ? 'badge-paid' : 'badge-unpaid' }}">{{ ucfirst($order->payment_method) }}, {{ ucfirst($order->payment_status) }}</span>
    </p>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="amount" style="width: 50px;">Qty</th>
                <th class="amount" style="width: 95px;">Unit price</th>
                <th class="amount" style="width: 95px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="amount">{{ $item->quantity }}</td>
                    <td class="amount">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="amount">₱{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Subtotal</td><td class="amount">₱{{ number_format($order->subtotal, 2) }}</td></tr>
        <tr><td class="label">Delivery fee</td><td class="amount">₱{{ number_format($order->delivery_fee, 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="amount">₱{{ number_format($order->total_amount, 2) }}</td></tr>
    </table>

    <p class="footer">Thank you for your order. This receipt was generated on {{ now()->format('M j, Y g:i A') }}.</p>
</body>
</html>