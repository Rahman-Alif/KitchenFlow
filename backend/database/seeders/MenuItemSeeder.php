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
            ['category_id' => 1, 'name' => 'Chicken Biryani',    'price' => 120, 'stock' => 50, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1563379091339-03246963d29d?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 1, 'name' => 'Beef Bhuna',         'price' => 150, 'stock' => 30, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1604908554007-13890fca4f8f?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 1, 'name' => 'Kacchi Biryani',     'price' => 180, 'stock' => 22, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1631515243349-e0cb75fb8d3a?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 1, 'name' => 'Khichuri Combo',     'price' => 110, 'stock' => 28, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 1, 'name' => 'Grilled Chicken',    'price' => 170, 'stock' => 18, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1598515214211-89d3c737e9f6?auto=format&fit=crop&w=800&q=80'],

            // Appetizers (category 2)
            ['category_id' => 2, 'name' => 'Chicken Soup',       'price' => 60,  'stock' => 25, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'Vegetable Roll',     'price' => 40,  'stock' => 60, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'Chicken Spring Roll','price' => 55,  'stock' => 30, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1601315576603-476d4d3ce8f5?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'French Fries',       'price' => 70,  'stock' => 40, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'Fish Finger',        'price' => 95,  'stock' => 20, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1585032226651-759b368d7246?auto=format&fit=crop&w=800&q=80'],

            // Desserts (category 3)
                ['category_id' => 3, 'name' => 'Mishti Doi',         'price' => 35,  'stock' => 20, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1470124182917-cc6e71b22ecc?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 3, 'name' => 'Rasgolla',           'price' => 30,  'stock' => 0,  'available' => false, 'image_path' => 'https://images.unsplash.com/photo-1514996937319-344454492b37?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 3, 'name' => 'Firni',              'price' => 50,  'stock' => 16, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 3, 'name' => 'Jilapi',             'price' => 25,  'stock' => 45, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1589118949245-7d38baf380d6?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 3, 'name' => 'Chocolate Brownie',  'price' => 80,  'stock' => 14, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=800&q=80'],

            // Beverages (category 4)
            ['category_id' => 4, 'name' => 'Mango Lassi',        'price' => 45,  'stock' => 35, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1570696516188-ade861b84a49?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 4, 'name' => 'Lemon Water',        'price' => 20,  'stock' => 100,'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1523362628745-0c100150b504?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 4, 'name' => 'Cold Coffee',        'price' => 90,  'stock' => 24, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 4, 'name' => 'Masala Tea',         'price' => 30,  'stock' => 60, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1594631661960-7adf839f3b8f?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 4, 'name' => 'Fresh Orange Juice', 'price' => 75,  'stock' => 26, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=800&q=80'],

            // Snacks (category 5)
            ['category_id' => 5, 'name' => 'Samosa',             'price' => 15,  'stock' => 80, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1601050690117-94f5f6fa11f7?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 5, 'name' => 'Piyaju',             'price' => 10,  'stock' => 0,  'available' => false, 'image_path' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 5, 'name' => 'Chicken Puff',       'price' => 35,  'stock' => 50, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 5, 'name' => 'Dal Puri',           'price' => 20,  'stock' => 42, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 5, 'name' => 'Egg Chop',           'price' => 25,  'stock' => 38, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1518492104633-130d0cc84637?auto=format&fit=crop&w=800&q=80'],

            // Special Menu (category 6)
            ['category_id' => 6, 'name' => 'Friday Special',      'price' => 200, 'stock' => 15, 'available' => true, 'image_path' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 6, 'name' => 'Chef Signature Plate','price' => 250, 'stock' => 12, 'available' => true, 'image_path' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 6, 'name' => 'Seafood Platter',     'price' => 300, 'stock' => 8,  'available' => true, 'image_path' => 'https://images.unsplash.com/photo-1615141982883-c7ad0e69fd62?auto=format&fit=crop&w=800&q=80'],
        ];

        foreach ($items as $item) {
            MenuItem::create([
                'category_id'         => $item['category_id'],
                'name'                => $item['name'],
                'price'               => $item['price'],
                'stock_quantity'      => $item['stock'],
                'low_stock_threshold' => 10,
                'is_available'        => $item['available'],
                'image_path'          => $item['image_path'] ?? null,
            ]);
        }
    }
}