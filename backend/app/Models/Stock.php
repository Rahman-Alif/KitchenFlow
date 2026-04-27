<?php

namespace App\Models;

use App\Traits\AuditableWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stock extends Model
{
    use SoftDeletes, AuditableWithUser;

    protected $table = 'stock';

    protected $fillable = [
        'menu_item_id',
        'current_quantity',
        'low_stock_threshold',
        'restock_level',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'restock_level' => 'integer',
    ];

    /**
     * Relationship: Stock belongs to MenuItem
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Relationship: Stock has many movements
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get latest movement for this stock
     */
    public function latestMovement()
    {
        return $this->hasOne(StockMovement::class)->latest();
    }

    /**
     * Check if stock is low
     */
    public function isLow(): bool
    {
        return $this->current_quantity <= $this->low_stock_threshold;
    }

    /**
     * Record a stock movement
     */
    public function recordMovement(string $type, int $quantity, string $reason = null): StockMovement
    {
        $previousQuantity = $this->current_quantity;
        $newQuantity = $previousQuantity + $quantity;

        $movement = $this->movements()->create([
            'movement_type' => $type,
            'quantity_changed' => $quantity,
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'reason' => $reason,
        ]);

        // Update current quantity
        $this->update(['current_quantity' => $newQuantity]);

        return $movement;
    }
}
