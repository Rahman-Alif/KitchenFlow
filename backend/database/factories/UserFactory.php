<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id'  => null,
            'name'       => fake()->name(),
            'email'      => fake()->unique()->safeEmail(),
            'password'   => Hash::make('password'),
            'role'       => 'user',
            'role_id'    => null,
            'is_active'  => true,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fn(array $a) => $a['created_at'],
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function kitchenStaff(): static
    {
        return $this->state(['role' => 'kitchen_staff']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}