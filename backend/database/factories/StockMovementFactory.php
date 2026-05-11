<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        $type     = fake()->randomElement(['initial', 'restock', 'sale', 'adjustment', 'damage', 'return']);
        $previous = fake()->numberBetween(0, 100);
        $changed  = match ($type) {
            'sale', 'damage'               => -fake()->numberBetween(1, max(1, min($previous, 10))),
            'initial', 'restock', 'return' => fake()->numberBetween(10, 100),
            'adjustment'                   => fake()->numberBetween(-10, 10),
        };

        return [
            'stock_id'          => null,
            'movement_type'     => $type,
            'quantity_changed'  => $changed,
            'previous_quantity' => $previous,
            'new_quantity'      => max(0, $previous + $changed),
            'reason'            => fake()->optional(0.4)->sentence(),
            'created_at'        => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at'        => fn(array $a) => $a['created_at'],
        ];
    }
}