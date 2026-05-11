<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id'     => null,
            'menu_item_id' => null,
            'quantity'     => fake()->numberBetween(1, 5),
            'unit_price'   => 0, // snapshotted from menu_item.price in seeder
        ];
    }
}