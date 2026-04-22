<?php

namespace App\Services\User;

use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Places an order for the authenticated user.
     *
     * Aborts:
     *   422 — empty items array
     *   422 — any item is unavailable or soft-deleted
     *   422 — any item belongs to a different tenant
     *   422 — insufficient stock for any item
     *
     * On success:
     *   - Snapshots unit_price from menu_items.price at placement time
     *   - Decrements stock atomically
     *   - Auto-disables item if stock hits zero (FR-04.1)
     *   - Persists total_amount — never recalculated after this point
     */
    public function place(int $userId, int $tenantId, array $items, ?string $notes): Order
    {
        if (empty($items)) {
            abort(422, 'At least one item is required.');
        }

        $menuItemIds = array_column($items, 'menu_item_id');

        $menuItems = MenuItem::whereIn('id', $menuItemIds)->with('category')->get()->keyBy('id');

        // Validate all items before any write
        foreach ($items as $item) {
            $menuItem = $menuItems->get($item['menu_item_id']);

            if (!$menuItem) {
                abort(422, "Menu item ID {$item['menu_item_id']} not found.");
            }

            if ($menuItem->category->tenant_id !== $tenantId) {
                abort(422, "Menu item ID {$item['menu_item_id']} does not belong to your organization.");
            }

            if (!$menuItem->is_available) {
                abort(422, "'{$menuItem->name}' is currently unavailable.");
            }

            if ($menuItem->stock_quantity < $item['quantity']) {
                abort(422, "Insufficient stock for '{$menuItem->name}'. Available: {$menuItem->stock_quantity}.");
            }
        }

        return DB::transaction(function () use ($userId, $items, $notes, $menuItems) {
            $total = 0;
            $orderItemsData = [];

            foreach ($items as $item) {
                $menuItem  = $menuItems->get($item['menu_item_id']);
                $unitPrice = $menuItem->price;
                $quantity  = $item['quantity'];

                $total += $unitPrice * $quantity;

                $orderItemsData[] = [
                    'menu_item_id' => $menuItem->id,
                    'quantity'     => $quantity,
                    'unit_price'   => $unitPrice,
                ];

                // Decrement stock atomically — floor is 0
                $menuItem->decrement('stock_quantity', $quantity);
                $menuItem->refresh();

                // Auto-disable if stock hits zero (FR-04.1)
                if ($menuItem->stock_quantity === 0) {
                    $menuItem->is_available = false;
                    $menuItem->save();
                }
            }

            $order = Order::create([
                'user_id'      => $userId,
                'status'       => 'pending',
                'total_amount' => $total,
                'notes'        => $notes,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            return $order->load(['orderItems.menuItem:id,name']);
        });
    }
}