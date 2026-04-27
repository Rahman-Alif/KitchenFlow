<?php

namespace App\Models;

use App\Traits\AuditableWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use AuditableWithUser;
    protected $fillable = [
        'order_id',
        'recorded_by',
        'tendered_amount',
        'change_returned',
    ];

    protected $casts = [
        'tendered_amount' => 'decimal:2',
        'change_returned' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recordedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
   {
    return $this->belongsTo(User::class, 'recorded_by');
   }
}

