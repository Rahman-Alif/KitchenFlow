<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create([
            'name' => 'admin',
            'description' => 'Administrator with full system access',
        ]);

        Role::create([
            'name' => 'kitchen_staff',
            'description' => 'Kitchen staff member responsible for food preparation',
        ]);

        Role::create([
            'name' => 'user',
            'description' => 'Regular user - customer or guest',
        ]);
    }
}
