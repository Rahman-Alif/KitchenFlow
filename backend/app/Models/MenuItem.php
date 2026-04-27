<?php

namespace App\Models;

use App\Traits\AuditableWithUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes, AuditableWithUser;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'image_path',
        'price',
        'stock_quantity',
        'low_stock_threshold',
        'is_available',
        'needs_restock',
        'requested_restock_quantity',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'needs_restock' => 'boolean',
        'requested_restock_quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}