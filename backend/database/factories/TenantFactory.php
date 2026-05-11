<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                 => fake()->company(),
            'subscription_active'  => true,
            'subscription_ends_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['subscription_active' => false]);
    }
}