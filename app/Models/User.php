<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles;

    protected $fillable = ['name', 'email', 'phone', 'password', 'status', 'is_available'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_available' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? 'none';
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function recurringOrders(): HasMany
    {
        return $this->hasMany(RecurringOrder::class, 'customer_id');
    }

    /** Deliveries assigned to this user when they are a rider. */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'rider_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function unreadNotificationCount(): int
    {
        return $this->appNotifications()->where('is_read', false)->count();
    }
}
