<?php

namespace Tests\Feature\KitchenStaff;

use App\Models\MenuItem;
use Tests\TestCase;

/**
 * TC28 – TC33 | Kitchen Staff — Transactions & Menu Availability
 *
 * Covers recording a valid cash transaction, tendered-amount validation,
 * duplicate transaction prevention, toggling availability off,
 * submitting a restock request, and the zero-stock availability guard.
 */
class KitchenStaffTransactionAndMenuTest extends TestCase
{
    // ─── TC28 ───────────────────────────────────────────────────────────────

    /**
     * TC28: Staff records a valid cash transaction on a pending order.
     * After recording, the order must transition to 'preparing' and
     * change_returned must equal tendered - total.
     */
    public function test_staff_records_valid_transaction_on_pending_order(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        $tendered = (float) $order->total_amount + 50;

        $response = $this->actingAs($staff, 'sanctum')
            ->postJson("/api/kitchen/orders/{$order->id}/transaction", [
                'tendered_amount' => $tendered,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'tendered_amount', 'change_returned', 'recorded_by'],
            ]);

        // Order must advance to preparing
        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'preparing',
        ]);

        // Change returned must be correct
        $this->assertDatabaseHas('transactions', [
            'order_id'        => $order->id,
            'change_returned' => number_format($tendered - $order->total_amount, 2, '.', ''),
        ]);
    }

    // ─── TC29 ───────────────────────────────────────────────────────────────

    /**
     * TC29: Transaction is rejected when the tendered amount is less than
     * the order total (FR-07.4).
     */
    public function test_transaction_rejected_when_tendered_less_than_total(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        // Give 1 BDT less than the total
        $tendered = max(0, (float) $order->total_amount - 1);

        $this->actingAs($staff, 'sanctum')
            ->postJson("/api/kitchen/orders/{$order->id}/transaction", [
                'tendered_amount' => $tendered,
            ])
            ->assertStatus(422);

        // Order must remain pending, no transaction created
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseMissing('transactions', ['order_id' => $order->id]);
    }

    // ─── TC30 ───────────────────────────────────────────────────────────────

    /**
     * TC30: A second transaction on the same order is rejected.
     * Transactions are immutable — one per order maximum.
     */
    public function test_duplicate_transaction_on_same_order_rejected(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        $tendered = (float) $order->total_amount + 10;

        // First transaction — must succeed
        $this->actingAs($staff, 'sanctum')
            ->postJson("/api/kitchen/orders/{$order->id}/transaction", [
                'tendered_amount' => $tendered,
            ])
            ->assertStatus(201);

        // Second transaction on the same order — must be rejected
        $this->actingAs($staff, 'sanctum')
            ->postJson("/api/kitchen/orders/{$order->id}/transaction", [
                'tendered_amount' => $tendered,
            ])
            ->assertStatus(422);
    }

    // ─── TC31 ───────────────────────────────────────────────────────────────

    /**
     * TC31: Staff can toggle an available item to unavailable.
     * Uses Chicken Biryani (is_available = true, stock > 0).
     */
    public function test_staff_can_disable_item_availability(): void
    {
        $staff = $this->kitchenStaff();
        $item  = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();

        $this->assertTrue($item->is_available);

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/menu/{$item->id}/availability", [
                'is_available' => false,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('menu_items', [
            'id'           => $item->id,
            'is_available' => false,
        ]);
    }

    // ─── TC32 ───────────────────────────────────────────────────────────────

    /**
     * TC32: Staff can submit a restock request for a low-stock item.
     * Validates that needs_restock = true and requested_restock_quantity is set.
     */
    public function test_staff_can_submit_restock_request(): void
    {
        $staff = $this->kitchenStaff();

        // Piyaju is seeded with low stock (5) and is available
        $item = MenuItem::where('name', 'Piyaju (4 pcs)')->firstOrFail();

        $this->actingAs($staff, 'sanctum')
            ->postJson("/api/kitchen/menu/{$item->id}/request-restock", [
                'quantity' => 50,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('menu_items', [
            'id'                         => $item->id,
            'needs_restock'              => true,
            'requested_restock_quantity' => 50,
        ]);
    }

    // ─── TC33 ───────────────────────────────────────────────────────────────

    /**
     * TC33: Staff cannot re-enable an item whose stock is 0.
     * Khichuri is seeded with stock_quantity = 0 and is_available = false.
     */
    public function test_staff_cannot_enable_zero_stock_item(): void
    {
        $staff = $this->kitchenStaff();
        $item  = MenuItem::where('name', 'Khichuri')->firstOrFail();

        $this->assertEquals(0, $item->stock_quantity);

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/menu/{$item->id}/availability", [
                'is_available' => true,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('menu_items', [
            'id'           => $item->id,
            'is_available' => false,
        ]);
    }
}
