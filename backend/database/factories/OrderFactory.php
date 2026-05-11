<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement([
            'served', 'served', 'served', 'served',
            'pending', 'preparing', 'ready', 'canceled',
        ]);

        return [
            'user_id'      => null,
            'status'       => $status,
            'total_amount' => 0, // recalculated in seeder after order_items are attached
            'notes'        => fake()->optional(0.3)->sentence(),
            'created_at'   => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at'   => fn(array $a) => $a['created_at'],
        ];
    }

    public function served(): static
    {
        return $this->state(['status' => 'served']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}