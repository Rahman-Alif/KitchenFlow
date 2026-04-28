<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Models\Stock;

class MenuItemObserver
{
    /**
     * When a menu item is created, also create a stock record
     * Demonstrates automatic stock initialization on menu item creation
     */
    public function created(MenuItem $menuItem): void
    {
        // Create stock record if it doesn't exist
        if (!$menuItem->stock()->exists()) {
            Stock::create([
                'menu_item_id' => $menuItem->id,
                'current_quantity' => $menuItem->stock_quantity,
                'low_stock_threshold' => $menuItem->low_stock_threshold,
                'restock_level' => $menuItem->low_stock_threshold * 5, // 5x threshold as restock level
            ]);

            // Record initial stock movement
            $stock = $menuItem->stock()->first();
            if ($stock) {
                $stock->recordMovement(
                    'initial',
                    $menuItem->stock_quantity,
                    'Initial stock from menu item creation'
                );
            }
        }
    }

    public function saving(MenuItem $menuItem): void
    {
        // Auto-disable only if stock reaches 0.
        // Low stock threshold is now just a warning indicator, not for availability.
        if ($menuItem->stock_quantity <= 0) {
            $menuItem->is_available = false;
        }
    }

    /**
     * When a menu item is updated, keep the stock table in sync
     */
    public function updated(MenuItem $menuItem): void
    {
        if ($menuItem->isDirty('stock_quantity')) {
            $stock = $menuItem->stock;
            if ($stock) {
                $stock->update(['current_quantity' => $menuItem->stock_quantity]);
                
                // Also record this manual edit as a movement for auditing
                $stock->recordMovement(
                    'adjustment',
                    $menuItem->stock_quantity - $stock->getOriginal('current_quantity'),
                    'Manual adjustment via Menu Item edit'
                );
            }
        }
    }
}

