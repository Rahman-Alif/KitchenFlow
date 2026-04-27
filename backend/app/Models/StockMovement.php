<?php

namespace App\Models;

use App\Traits\AuditableWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends Model
{
    use SoftDeletes, AuditableWithUser;

    protected $table = 'stock_movements';

    protected $fillable = [
        'stock_id',
        'movement_type',
        'quantity_changed',
        'previous_quantity',
        'new_quantity',
        'reason',
    ];

    protected $casts = [
        'quantity_changed' => 'integer',
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];

    /**
     * Relationship: StockMovement belongs to Stock
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
