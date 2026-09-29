<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id', 'rider_id', 'address_id', 'status', 'assigned_at', 'dispatched_at', 'delivered_at', 'notes',
        'last_lat', 'last_lng', 'location_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'location_updated_at' => 'datetime',
            'last_lat' => 'float',
            'last_lng' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }
}
