<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'name'                 => 'Betopia Canteen',
            'subscription_active'  => true,
            'subscription_ends_at' => now()->addYear(),
        ]);

        Tenant::create([
            'name'                 => 'Inactive Org',
            'subscription_active'  => false,
            'subscription_ends_at' => now()->subDay(),
        ]);
    }
}