<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Main Course',
            'Appetizers',
            'Desserts',
            'Beverages',
            'Snacks',
            'Special Menu',
        ];

        foreach ($categories as $name) {
            Category::create([
                'tenant_id' => 1,
                'name'      => $name,
            ]);
        }
    }
}