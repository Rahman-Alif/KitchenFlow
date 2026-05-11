<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'  => null,
            'name'       => 'Uncategorized', // always overridden by seeder
            'created_at' => fake()->dateTimeBetween('-6 months', '-5 months'),
            'updated_at' => fn(array $a) => $a['created_at'],
        ];
    }
}