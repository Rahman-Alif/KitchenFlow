<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_item_id'        => null,
            'current_quantity'    => fake()->numberBetween(0, 150),
            'low_stock_threshold' => fake()->numberBetween(5, 20),
            'restock_level'       => fake()->numberBetween(50, 200),
            'created_at'          => fake()->dateTimeBetween('-6 months', '-4 months'),
            'updated_at'          => fn(array $a) => $a['created_at'],
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn(array $a) => [
            'current_quantity' => fake()->numberBetween(0, $a['low_stock_threshold']),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(['current_quantity' => 0]);
    }
}