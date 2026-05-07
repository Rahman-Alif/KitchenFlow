<?php

namespace Tests;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Tell RefreshDatabase to run the seeder automatically once after
     * migrate:fresh — do NOT call $this->seed() in setUp(), that causes
     * duplicate-key violations when RefreshDatabase wraps each test in
     * a transaction and the seeder runs again inside it.
     */
    protected bool $seed = true;
    protected string $seeder = \Database\Seeders\DatabaseSeeder::class;

    // ─── Auth helpers ────────────────────────────────────────────────────────

    protected function admin(): User
    {
        return User::where('email', 'sarah.ahmed@nexuscorp.com')->firstOrFail();
    }

    protected function kitchenStaff(): User
    {
        return User::where('email', 'priya.nair@nexuscorp.com')->firstOrFail();
    }

    protected function customer(): User
    {
        return User::where('email', 'tanvir.mahmud@nexuscorp.com')->firstOrFail();
    }

    protected function orionAdmin(): User
    {
        return User::where('email', 'david.park@orionlabs.io')->firstOrFail();
    }

    // ─── Data helpers ─────────────────────────────────────────────────────────

    protected function availableItem(): MenuItem
    {
        return MenuItem::where('is_available', true)
            ->where('stock_quantity', '>', 0)
            ->whereHas('category', fn($q) => $q->where('name', '!=', 'Seasonal Specials')->whereNull('deleted_at'))
            ->firstOrFail();
    }

    protected function pendingOrder(): Order
    {
        return Order::where('status', 'pending')
            ->whereHas('user', fn($q) => $q->where('tenant_id', $this->admin()->tenant_id))
            ->firstOrFail();
    }

    protected function preparingOrder(): Order
    {
        return Order::where('status', 'preparing')
            ->whereHas('user', fn($q) => $q->where('tenant_id', $this->admin()->tenant_id))
            ->firstOrFail();
    }

    protected function readyOrder(): Order
    {
        return Order::where('status', 'ready')
            ->whereHas('user', fn($q) => $q->where('tenant_id', $this->admin()->tenant_id))
            ->firstOrFail();
    }

    protected function servedOrder(): Order
    {
        return Order::where('status', 'served')
            ->whereHas('user', fn($q) => $q->where('tenant_id', $this->admin()->tenant_id))
            ->firstOrFail();
    }

    protected function placeOrderAs(User $user, ?array $overrideItems = null): Order
    {
        $item  = $this->availableItem();
        $items = $overrideItems ?? [['menu_item_id' => $item->id, 'quantity' => 1]];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/orders', ['items' => $items]);

        $response->assertStatus(201);
        return Order::find($response->json('data.id'));
    }
}