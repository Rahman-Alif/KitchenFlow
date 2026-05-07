<?php

namespace Tests\Feature\User;

use App\Models\MenuItem;
use Tests\TestCase;

/**
 * TC36 – TC40 | Customer — Order Placement
 *
 * Covers successful order placement with stock decrement, rejection for
 * unavailable items, insufficient stock, cross-tenant item abuse,
 * and auto-disabling an item when the last unit is ordered.
 */
class CustomerOrderPlacementTest extends TestCase
{
    // ─── TC36 ───────────────────────────────────────────────────────────────

    /**
     * TC36: Customer places a valid order.
     * Stock must decrement by the ordered quantity after placement.
     */
    public function test_customer_can_place_valid_order_and_stock_decrements(): void
    {
        $customer = $this->customer();
        $item     = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();
        $stockBefore = $item->stock_quantity;

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $item->id, 'quantity' => 2]],
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'total_amount', 'items'],
            ])
            ->assertJsonPath('data.status', 'pending');

        $this->assertEquals($stockBefore - 2, $item->fresh()->stock_quantity);
    }

    // ─── TC37 ───────────────────────────────────────────────────────────────

    /**
     * TC37: Order is rejected when a requested item is marked unavailable.
     * Khichuri is seeded as is_available = false.
     */
    public function test_order_rejected_for_unavailable_item(): void
    {
        $customer = $this->customer();
        $item     = MenuItem::where('name', 'Khichuri')->firstOrFail();

        $this->assertFalse($item->is_available);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    // ─── TC38 ───────────────────────────────────────────────────────────────

    /**
     * TC38: Order is rejected when requested quantity exceeds current stock.
     * Piyaju has only 5 portions in stock — ordering 10 must fail.
     */
    public function test_order_rejected_when_quantity_exceeds_stock(): void
    {
        $customer = $this->customer();
        $item     = MenuItem::where('name', 'Piyaju (4 pcs)')->firstOrFail();

        $this->assertLessThan(10, $item->stock_quantity);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $item->id, 'quantity' => 10]],
            ])
            ->assertStatus(422);

        // Stock must be unchanged
        $this->assertEquals($item->stock_quantity, $item->fresh()->stock_quantity);
    }

    // ─── TC39 ───────────────────────────────────────────────────────────────

    /**
     * TC39: A Nexus customer cannot order an item that belongs to Orion Labs.
     * Cross-tenant item access must be rejected with 422.
     */
    public function test_order_rejected_for_cross_tenant_item(): void
    {
        $customer  = $this->customer(); // Nexus tenant
        $orionItem = MenuItem::where('name', 'Pasta Bolognese')->firstOrFail();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $orionItem->id, 'quantity' => 1]],
            ])
            ->assertStatus(422);
    }

    // ─── TC40 ───────────────────────────────────────────────────────────────

    /**
     * TC40: When an order depletes the last unit of an item, is_available
     * is automatically set to false (FR-04.1).
     * Piyaju has 5 units — ordering all 5 must auto-disable it.
     */
    public function test_item_auto_disabled_when_stock_hits_zero(): void
    {
        $customer = $this->customer();
        $item     = MenuItem::where('name', 'Piyaju (4 pcs)')->firstOrFail();
        $qty      = $item->stock_quantity; // exactly 5

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $item->id, 'quantity' => $qty]],
            ])
            ->assertStatus(201);

        $item->refresh();
        $this->assertEquals(0, $item->stock_quantity);
        $this->assertFalse($item->is_available);
    }
}
