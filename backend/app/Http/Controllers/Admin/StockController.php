<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockController extends Controller
{
    /**
     * Get all stock records with current quantities
     * Demonstrates the Stock table
     */
    public function index(): JsonResource
    {
        $stocks = Stock::with('menuItem')
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'menu_item' => [
                        'id' => $stock->menuItem->id,
                        'name' => $stock->menuItem->name,
                        'price' => $stock->menuItem->price,
                    ],
                    'current_quantity' => $stock->current_quantity,
                    'low_stock_threshold' => $stock->low_stock_threshold,
                    'restock_level' => $stock->restock_level,
                    'is_low' => $stock->isLow(),
                    'movements_count' => $stock->movements()->count(),
                    'last_movement' => $stock->latestMovement?->load('stock')->toArray(),
                ];
            });

        return JsonResource::make([
            'success' => true,
            'data' => $stocks,
            'message' => 'All stock records retrieved - demonstrates Stock table',
        ]);
    }

    /**
     * Get stock history (movements) for a specific menu item
     * Demonstrates FK relationship and stock movements table
     */
    public function history(MenuItem $menuItem): JsonResource
    {
        $stock = $menuItem->stock;

        if (!$stock) {
            return JsonResource::make([
                'success' => false,
                'data' => null,
                'message' => 'No stock record found for this menu item',
            ]);
        }

        $movements = $stock->movements()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'type' => $movement->movement_type,
                    'quantity_changed' => $movement->quantity_changed,
                    'previous_quantity' => $movement->previous_quantity,
                    'new_quantity' => $movement->new_quantity,
                    'reason' => $movement->reason,
                    'created_at' => $movement->created_at,
                ];
            });

        return JsonResource::make([
            'success' => true,
            'data' => [
                'menu_item' => [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                ],
                'stock' => [
                    'id' => $stock->id,
                    'current_quantity' => $stock->current_quantity,
                    'low_stock_threshold' => $stock->low_stock_threshold,
                    'restock_level' => $stock->restock_level,
                    'is_low' => $stock->isLow(),
                ],
                'movements' => $movements,
                'movements_count' => $movements->count(),
            ],
            'message' => 'Stock history retrieved - demonstrates stock movements table and audit trail',
        ]);
    }

    /**
     * Restock a menu item
     * Demonstrates recording a stock movement when restocking happens
     */
    public function restock(Request $request, MenuItem $menuItem): JsonResource
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $stock = $menuItem->stock;

        if (!$stock) {
            return JsonResource::make([
                'success' => false,
                'data' => null,
                'message' => 'No stock record found for this menu item',
            ]);
        }

        // Record the restock movement
        $movement = $stock->recordMovement(
            'restock',
            $validated['quantity'],
            $validated['reason'] ?? 'Admin restock'
        );

        return JsonResource::make([
            'success' => true,
            'data' => [
                'menu_item_id' => $menuItem->id,
                'menu_item_name' => $menuItem->name,
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->movement_type,
                    'quantity_changed' => $movement->quantity_changed,
                    'previous_quantity' => $movement->previous_quantity,
                    'new_quantity' => $movement->new_quantity,
                    'created_at' => $movement->created_at,
                ],
                'current_stock' => $stock->current_quantity,
            ],
            'message' => 'Stock restocked successfully - demonstrates stock movement recording',
        ]);
    }

    /**
     * Simulate a sale by reducing stock
     * Demonstrates recording stock movement when item is sold
     */
    public function recordSale(Request $request, MenuItem $menuItem): JsonResource
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $stock = $menuItem->stock;

        if (!$stock) {
            return JsonResource::make([
                'success' => false,
                'data' => null,
                'message' => 'No stock record found for this menu item',
            ]);
        }

        if ($stock->current_quantity < $validated['quantity']) {
            return JsonResource::make([
                'success' => false,
                'data' => null,
                'message' => 'Insufficient stock for this sale',
            ]);
        }

        // Record the sale movement (negative quantity)
        $movement = $stock->recordMovement(
            'sale',
            -$validated['quantity'],
            'Sale recorded'
        );

        return JsonResource::make([
            'success' => true,
            'data' => [
                'menu_item_id' => $menuItem->id,
                'menu_item_name' => $menuItem->name,
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->movement_type,
                    'quantity_changed' => $movement->quantity_changed,
                    'previous_quantity' => $movement->previous_quantity,
                    'new_quantity' => $movement->new_quantity,
                    'created_at' => $movement->created_at,
                ],
                'current_stock' => $stock->current_quantity,
                'is_low' => $stock->isLow(),
            ],
            'message' => 'Sale recorded - stock reduced and movement tracked',
        ]);
    }
}
