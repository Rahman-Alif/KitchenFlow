<?php

namespace Tests\Feature\User;

use Tests\TestCase;

/**
 * TC41 – TC45 | Customer — Order Management
 *
 * Covers order history, single order detail, cancellation with stock
 * restoration, rejection of canceling non-pending orders, and
 * cross-user order access prevention.
 */
class CustomerOrderManagementTest extends TestCase
{
    // ─── TC41 ───────────────────────────────────────────────────────────────

    /**
     * TC41: Customer can retrieve their own order history.
     * Only their own orders must be returned — not other users' orders.
     */
    public function test_customer_can_view_own_order_history(): void
    {
        $customer = $this->customer();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/user/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        // All returned orders must belong to this user
        collect($response->json('data'))->each(function (array $order) use ($customer) {
            $this->assertEquals($customer->id, $order['user_id'] ?? $order['user']['id'] ?? null);
        });
    }

    // ─── TC42 ───────────────────────────────────────────────────────────────

    /**
     * TC42: Customer can view detail of a single order they own,
     * including the ordered items and their unit prices.
     */
    public function test_customer_can_view_own_order_detail(): void
    {
        $customer = $this->customer();

        // Place a fresh order so we have a known order owned by this customer
        $order = $this->placeOrderAs($customer);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("/api/user/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total_amount', 'items'],
            ]);
    }

    // ─── TC43 ───────────────────────────────────────────────────────────────

    /**
     * TC43: Customer can cancel a pending order and stock is restored.
     * After cancellation, the item stock must return to its pre-order level.
     */
    public function test_customer_cancels_pending_order_and_stock_is_restored(): void
    {
        $customer = $this->customer();
        $item     = \App\Models\MenuItem::where('name', 'Vegetable Samosa (2 pcs)')->firstOrFail();
        $stockBefore = $item->stock_quantity;

        $order = $this->placeOrderAs($customer, [
            ['menu_item_id' => $item->id, 'quantity' => 2],
        ]);

        $stockAfterOrder = $item->fresh()->stock_quantity;
        $this->assertEquals($stockBefore - 2, $stockAfterOrder);

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/user/orders/{$order->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'canceled');

        // Stock must be restored to pre-order level
        $this->assertEquals($stockBefore, $item->fresh()->stock_quantity);
    }

    // ─── TC44 ───────────────────────────────────────────────────────────────

    /**
     * TC44: A customer cannot cancel an order that is no longer pending.
     * Uses a served order from the seeder — cancellation must return 422.
     */
    public function test_customer_cannot_cancel_non_pending_order(): void
    {
        // Find a served order owned by the customer
        $customer = $this->customer();
        $order    = \App\Models\Order::where('user_id', $customer->id)
            ->where('status', 'served')
            ->firstOrFail();

        $this->actingAs($customer, 'sanctum')
            ->patchJson("/api/user/orders/{$order->id}/cancel")
            ->assertStatus(422);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'served',
        ]);
    }

    // ─── TC45 ───────────────────────────────────────────────────────────────

    /**
     * TC45: A customer cannot view another user's order.
     * Nusrat's orders must not be accessible by Tanvir.
     */
    public function test_customer_cannot_view_another_users_order(): void
    {
        $tanvir = $this->customer();
        $nusrat = \App\Models\User::where('email', 'nusrat.jahan@nexuscorp.com')->firstOrFail();

        // Get an order that belongs to Nusrat
        $nusratOrder = \App\Models\Order::where('user_id', $nusrat->id)->firstOrFail();

        $this->actingAs($tanvir, 'sanctum')
            ->getJson("/api/user/orders/{$nusratOrder->id}")
            ->assertStatus(403);
    }
}
