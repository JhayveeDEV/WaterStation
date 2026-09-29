<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class RecurringOrder extends Model
{
    protected $fillable = [
        'customer_id', 'source_order_id', 'last_order_id', 'delivery_method', 'address_id',
        'contact_phone', 'schedule_slot', 'frequency', 'next_order_date', 'items',
        'customer_notes', 'is_active', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'next_order_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sourceOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'source_order_id');
    }

    public function lastOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'last_order_id');
    }

    public function nextDateAfter(Carbon $cutoff): Carbon
    {
        $date = $this->next_order_date->copy();

        do {
            $date = match ($this->frequency) {
                'biweekly' => $date->addWeeks(2),
                'monthly' => $date->addMonthNoOverflow(),
                default => $date->addWeek(),
            };
        } while ($date->lte($cutoff));

        return $date;
    }
}
