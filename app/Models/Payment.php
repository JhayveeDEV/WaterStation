<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'provider', 'reference', 'amount', 'status', 'paid_at', 'refund_status', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'refunded_at' => 'datetime'];
    }

    public function needsRefund(): bool
    {
        return $this->refund_status === 'pending';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
