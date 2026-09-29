<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    /** Format: WS-20260922-0001, reset daily, safe under concurrent checkouts. */
    public static function next(): string
    {
        $prefix = 'WS-'.now()->format('Ymd').'-';

        return DB::transaction(function () use ($prefix) {
            $count = Order::where('order_number', 'like', $prefix.'%')->lockForUpdate()->count();

            return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
