<?php

namespace App\Services\KitchenStaff;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;

class MenuAvailabilityService
{
    /**
     * Returns all non-deleted menu items for the given tenant.
     * Kitchen Staff sees all items regardless of availability.
     */
    public function getAllItems(int $tenantId): Collection
    {
        return MenuItem::whereHas('category', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->with('category:id,name')
            ->oldest('name')
            ->get();
    }

    /**
     * Toggles is_available on a menu item.
     * Aborts 403 on tenant mismatch.
     * Aborts 422 if trying to enable an item with zero stock.
     */
    public function updateAvailability(int $itemId, bool $isAvailable, int $tenantId): MenuItem
    {
        $item = MenuItem::with('category:id,name,tenant_id')->findOrFail($itemId);
        if ($item->category->tenant_id !== $tenantId) {
            abort(403, 'Menu item does not belong to your organization.');
        }

        if ($isAvailable && $item->stock_quantity === 0) {
            abort(422, 'Cannot enable an item with zero stock. Please restock first.');
        }

        $item->is_available = $isAvailable;
        $item->save();

        return $item;
    }

    /**
     * Sets needs_restock to true for a menu item.
     * Aborts 403 on tenant mismatch.
     */
    public function requestRestock(int $itemId, int $tenantId, ?int $quantity = null): MenuItem
    {
        $item = MenuItem::with('category:id,name,tenant_id')->findOrFail($itemId);
        if ($item->category->tenant_id !== $tenantId) {
            abort(403, 'Menu item does not belong to your organization.');
        }

        $item->needs_restock = true;
        if ($quantity !== null) {
            $item->requested_restock_quantity = $quantity;
        }
        $item->save();

        return $item;
    }
}