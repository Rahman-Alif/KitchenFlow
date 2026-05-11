<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id'        => null,
            'recorded_by'     => null,
            'tendered_amount' => 0,
            'change_returned' => 0,
            'created_at'      => null,
            'updated_at'      => null,
        ];
    }
}