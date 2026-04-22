<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Main Course (category 1)
            ['category_id' => 1, 'name' => 'Chicken Biryani',    'price' => 120, 'stock' => 50, 'available' => true],
            ['category_id' => 1, 'name' => 'Beef Bhuna',         'price' => 150, 'stock' => 30, 'available' => true],
            ['category_id' => 1, 'name' => 'Vegetable Khichuri', 'price' => 80,  'stock' => 40, 'available' => true],

            // Appetizers (category 2)
            ['category_id' => 2, 'name' => 'Chicken Soup',       'price' => 60,  'stock' => 25, 'available' => true],
            ['category_id' => 2, 'name' => 'Vegetable Roll',     'price' => 40,  'stock' => 60, 'available' => true],

            // Desserts (category 3)
            ['category_id' => 3, 'name' => 'Mishti Doi',         'price' => 35,  'stock' => 20, 'available' => true],
            ['category_id' => 3, 'name' => 'Rasgolla',           'price' => 30,  'stock' => 0,  'available' => false],

            // Beverages (category 4)
            ['category_id' => 4, 'name' => 'Mango Lassi',        'price' => 45,  'stock' => 35, 'available' => true],
            ['category_id' => 4, 'name' => 'Lemon Water',        'price' => 20,  'stock' => 100,'available' => true],

            // Snacks (category 5)
            ['category_id' => 5, 'name' => 'Samosa',             'price' => 15,  'stock' => 80, 'available' => true],
            ['category_id' => 5, 'name' => 'Piyaju',             'price' => 10,  'stock' => 0,  'available' => false],

            // Special Menu (category 6)
            ['category_id' => 6, 'name' => 'Friday Special',     'price' => 200, 'stock' => 15, 'available' => true],
        ];

        foreach ($items as $item) {
            MenuItem::create([
                'category_id'         => $item['category_id'],
                'name'                => $item['name'],
                'price'               => $item['price'],
                'stock_quantity'      => $item['stock'],
                'low_stock_threshold' => 10,
                'is_available'        => $item['available'],
            ]);
        }
    }
}