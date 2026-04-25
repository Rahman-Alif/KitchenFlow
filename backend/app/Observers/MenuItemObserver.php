<?php

namespace App\Observers;

use App\Models\MenuItem;

class MenuItemObserver
{
    public function saving(MenuItem $menuItem): void
    {
        // Auto-disable if stock is at or below threshold.
        // Never auto-enable; re-enabling must be an explicit admin action.
        if ($menuItem->stock_quantity <= $menuItem->low_stock_threshold) {
            $menuItem->is_available = false;
        }
    }
}
