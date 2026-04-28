<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin'], [
            'description' => 'Administrator with full system access',
        ]);

        Role::firstOrCreate(['name' => 'kitchen_staff'], [
            'description' => 'Kitchen staff member responsible for food preparation',
        ]);

        Role::firstOrCreate(['name' => 'user'], [
            'description' => 'Regular user - customer or guest',
        ]);
    }
}