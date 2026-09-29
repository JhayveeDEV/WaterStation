<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'module', 'action', 'reference_id', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Record an important action (who, what, where, when). */
    public static function record(string $module, string $action, ?int $referenceId = null, ?string $description = null, ?int $userId = null): void
    {
        static::create([
            'user_id' => $userId ?? auth()->id(),
            'module' => $module,
            'action' => $action,
            'reference_id' => $referenceId,
            'description' => $description,
        ]);
    }
}
