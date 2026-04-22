<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant 1 users
        User::create([
            'tenant_id' => 1,
            'name'      => 'Alif Admin',
            'email'     => 'alif@betopia.com',
            'password'  => 'password123',
            'role'      => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => 1,
            'name'      => 'Shuvo Kitchen',
            'email'     => 'shuvo@betopia.com',
            'password'  => 'password123',
            'role'      => 'kitchen_staff',
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => 1,
            'name'      => 'John User',
            'email'     => 'john@betopia.com',
            'password'  => 'password123',
            'role'      => 'user',
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => 1,
            'name'      => 'Jane User',
            'email'     => 'jane@betopia.com',
            'password'  => 'password123',
            'role'      => 'user',
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => 1,
            'name'      => 'Inactive Staff',
            'email'     => 'inactive@betopia.com',
            'password'  => 'password123',
            'role'      => 'kitchen_staff',
            'is_active' => false,
        ]);

        // Tenant 2 user (for cross-tenant test)
        User::create([
            'tenant_id' => 2,
            'name'      => 'Other Org Admin',
            'email'     => 'admin@otherog.com',
            'password'  => 'password123',
            'role'      => 'admin',
            'is_active' => true,
        ]);
    }
}