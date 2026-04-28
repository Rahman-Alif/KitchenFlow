<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $duplicateGroups = MenuItem::query()
            ->select('category_id', 'name')
            ->groupBy('category_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $records = MenuItem::query()
                ->where('category_id', $group->category_id)
                ->where('name', $group->name)
                ->orderBy('id')
                ->get();

            $keep = $records->first();
            $duplicateIds = $records->skip(1)->pluck('id')->all();

            if (!$keep || empty($duplicateIds)) {
                continue;
            }

            DB::table('order_items')
                ->whereIn('menu_item_id', $duplicateIds)
                ->update(['menu_item_id' => $keep->id]);

            MenuItem::query()->whereIn('id', $duplicateIds)->delete();
        }

        $items = [
            // Main Course (category 1)
            ['category_id' => 1, 'name' => 'Chicken Biryani',    'price' => 120, 'stock' => 50, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1631515243349-e0cb75fb8d3a?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 1, 'name' => 'Beef Bhuna',         'price' => 150, 'stock' => 30, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/b4/56/11/b45611968f5f063d647eadbdfdb32f3a.jpg'],
            ['category_id' => 1, 'name' => 'Kacchi Biryani',     'price' => 180, 'stock' => 22, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/b9/4d/b9/b94db9d739919cf7c31d04004aefebee.jpg'],
            ['category_id' => 1, 'name' => 'Khichuri Combo',     'price' => 110, 'stock' => 28, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/3a/8b/00/3a8b00da554349060b36da3a8225032e.jpg'],
            ['category_id' => 1, 'name' => 'Grilled Chicken',    'price' => 170, 'stock' => 18, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/38/6f/ca/386fca8205bf6a76a954af0a66ae0178.jpg'],
            ['category_id' => 1, 'name' => 'Mutton Rezala',      'price' => 220, 'stock' => 15, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/bf/58/11/bf58117d56f6aa5cb0c12b3a00859e2b.jpg', 'description' => 'A classic Bengali style mutton curry with a rich, aromatic gravy.'],

            // Appetizers (category 2)
            ['category_id' => 2, 'name' => 'Chicken Soup',       'price' => 60,  'stock' => 25, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'Vegetable Roll',     'price' => 40,  'stock' => 60, 'available' => true,  'image_path' => 'https://i.pinimg.com/control1/1200x/d7/62/63/d76263919f64495d60ec9b7a0b0f9172.jpg'],
            ['category_id' => 2, 'name' => 'Chicken Spring Roll','price' => 55,  'stock' => 30, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/4e/ef/68/4eef689dafa4a856a39497fbcb7175b2.jpg'],
            ['category_id' => 2, 'name' => 'French Fries',       'price' => 70,  'stock' => 40, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/57/22/00/57220047fc59da5722f2daf2bf683b67.jpg'],
            ['category_id' => 2, 'name' => 'Fish Finger',        'price' => 95,  'stock' => 20, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 2, 'name' => 'Garlic Bread',      'price' => 85,  'stock' => 30, 'available' => true,  'image_path' => 'https://i.pinimg.com/control1/736x/c7/ea/52/c7ea525037dbdc48af0003520fc33163.jpg', 'description' => 'Crispy baguette slices topped with rich garlic butter and melted mozzarella.'],

            // Desserts (category 3)
            ['category_id' => 3, 'name' => 'Mishti Doi',         'price' => 35,  'stock' => 20, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/ea/b1/5f/eab15f83311c79a7a3f267f80b5290c6.jpg'],
            ['category_id' => 3, 'name' => 'Rasgolla',           'price' => 30,  'stock' => 0,  'available' => false, 'image_path' => 'https://i.pinimg.com/1200x/34/cf/63/34cf6386c850d334889c17ce6b235657.jpg'],
            ['category_id' => 3, 'name' => 'Firni',              'price' => 50,  'stock' => 16, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/47/3c/ba/473cbaf4d8632335014c3e687e03d089.jpg'],
            ['category_id' => 3, 'name' => 'Jilapi',             'price' => 25,  'stock' => 45, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/74/08/ad/7408add437d1ebc02bc0a1377bb92f3b.jpg'],
            ['category_id' => 3, 'name' => 'Chocolate Brownie',  'price' => 80,  'stock' => 14, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 3, 'name' => 'Gulab Jamun',        'price' => 40,  'stock' => 50, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/e6/51/46/e651469a60a2a99adfb2e8cd1fdbc6a4.jpg', 'description' => 'Soft, melt-in-your-mouth milk solids soaked in cardamom-infused sugar syrup.'],

            // Beverages (category 4)
            ['category_id' => 4, 'name' => 'Mango Lassi',        'price' => 45,  'stock' => 35, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/a7/c3/74/a7c374b66ff0139c459fddf680ef818a.jpg'],
            ['category_id' => 4, 'name' => 'Strawberry Mojito',  'price' => 85,  'stock' => 100,'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1546171753-97d7676e4602?auto=format&fit=crop&w=800&q=80', 'description' => 'A refreshing blend of fresh strawberries, mint leaves, lime, and soda.'],
            ['category_id' => 4, 'name' => 'Cold Coffee',        'price' => 90,  'stock' => 24, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 4, 'name' => 'Masala Tea',         'price' => 30,  'stock' => 60, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/7c/a6/54/7ca65499d4a7d6ee290c2edd656a46f2.jpg'],
            ['category_id' => 4, 'name' => 'Fresh Orange Juice', 'price' => 75,  'stock' => 26, 'available' => true,  'image_path' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=800&q=80'],

            // Snacks (category 5)
            ['category_id' => 5, 'name' => 'Samosa',             'price' => 15,  'stock' => 80, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/1b/da/ca/1bdaca54b40441bc8a1bccc733e3ca43.jpg'],
            ['category_id' => 5, 'name' => 'Piyaju',             'price' => 10,  'stock' => 0,  'available' => false, 'image_path' => 'https://i.pinimg.com/1200x/30/9d/8f/309d8fab32ece40b6bcb6858fda799e7.jpg'],
            ['category_id' => 5, 'name' => 'Chicken Puff',       'price' => 35,  'stock' => 50, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/98/b9/cb/98b9cb5f16046cf503521c679d18b492.jpg'],
            ['category_id' => 5, 'name' => 'Dal Puri',           'price' => 20,  'stock' => 42, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/1e/20/9a/1e209abf01f56f150e5a8fe1d1964c42.jpg'],
            ['category_id' => 5, 'name' => 'Egg Chop',           'price' => 25,  'stock' => 38, 'available' => true,  'image_path' => 'https://i.pinimg.com/736x/fc/a9/ec/fca9ec4ca94ffbc99b72b3f75f26e988.jpg'],
            ['category_id' => 5, 'name' => 'Potato Wedges',      'price' => 65,  'stock' => 45, 'available' => true,  'image_path' => 'https://i.pinimg.com/1200x/b5/60/3e/b5603ee13f283a4ed950209260d83f9e.jpg', 'description' => 'Crispy on the outside, fluffy on the inside. Seasoned with a special herb blend.'],

            // Special Menu (category 6)
            ['category_id' => 6, 'name' => 'Friday Special',      'price' => 200, 'stock' => 15, 'available' => true, 'image_path' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 6, 'name' => 'Chef Signature Plate','price' => 250, 'stock' => 12, 'available' => true, 'image_path' => 'https://images.unsplash.com/photo-1559847844-5315695dadae?auto=format&fit=crop&w=800&q=80'],
            ['category_id' => 6, 'name' => 'Seafood Platter',     'price' => 300, 'stock' => 8,  'available' => true, 'image_path' => 'https://i.pinimg.com/1200x/cf/77/ba/cf77bab17027dc35ca19087a685f9c4d.jpg'],
            ['category_id' => 6, 'name' => 'Mutton Kacchi',      'price' => 280, 'stock' => 15, 'available' => true, 'image_path' => 'https://i.pinimg.com/736x/a4/66/9a/a4669a419a1d51fc927182f6660bfb3e.jpg', 'description' => 'Traditional Basmati rice Kacchi Biryani with tender mutton pieces and aromatic spices.'],
            ['category_id' => 6, 'name' => 'Whole Grilled Fish', 'price' => 450, 'stock' => 5,  'available' => true, 'image_path' => 'https://i.pinimg.com/736x/7a/f1/63/7af163cae20bf8b0bb15057370d8bb61.jpg', 'description' => 'Freshly caught red snapper, marinated in secret spices and grilled to perfection over charcoal.'],
            ['category_id' => 6, 'name' => 'Royal Family Platter','price' => 999, 'stock' => 3,  'available' => true, 'image_path' => 'https://i.pinimg.com/736x/05/85/3a/05853ab2448b448490b5982c3eda0a14.jpg', 'description' => 'A massive feast for 4! Includes Biryani, Grilled Chicken, Seafood, Appetizers, and Drinks.'],
        ];

        foreach ($items as $item) {
            MenuItem::firstOrCreate(
                [
                    'category_id' => $item['category_id'],
                    'name'        => $item['name'],
                ],
                [
                    'price'               => $item['price'],
                    'description'         => $item['description'] ?? null,
                    'stock_quantity'      => $item['stock'],
                    'low_stock_threshold' => 10,
                    'is_available'        => $item['available'],
                    'image_path'          => $item['image_path'] ?? null,
                ]
            );
        }
    }
}