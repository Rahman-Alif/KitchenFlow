<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    /**
     * Seed initial stock for all existing menu items
     * Demonstrates stock table initialization and stock movement recording
     */
    public function run(): void
    {
        $menuItems = MenuItem::doesntHave('stock')->get();

        foreach ($menuItems as $menuItem) {
            // Create stock record
            $stock = Stock::create([
                'menu_item_id' => $menuItem->id,
                'current_quantity' => $menuItem->stock_quantity,
                'low_stock_threshold' => $menuItem->low_stock_threshold,
                'restock_level' => $menuItem->low_stock_threshold * 5,
            ]);

            // Record initial movement
            $stock->recordMovement(
                'initial',
                $menuItem->stock_quantity,
                'Initial stock from seed'
            );
        }
    }
}
