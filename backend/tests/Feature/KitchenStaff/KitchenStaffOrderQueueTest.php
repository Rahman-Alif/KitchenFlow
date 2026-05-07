<?php

namespace Tests\Feature\KitchenStaff;

use App\Models\Order;
use Tests\TestCase;

/**
 * TC23 – TC27 | Kitchen Staff — Order Queue
 *
 * Covers queue visibility, valid status transitions (pending→canceled,
 * preparing→ready, ready→served), and rejection of invalid transitions.
 */
class KitchenStaffOrderQueueTest extends TestCase
{
    // ─── TC23 ───────────────────────────────────────────────────────────────

    /**
     * TC23: Staff sees only active orders (pending, preparing, ready).
     * Served and canceled orders must NOT appear in the queue.
     */
    public function test_staff_sees_only_active_orders_in_queue(): void
    {
        $staff    = $this->kitchenStaff();
        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/kitchen/orders');

        $response->assertStatus(200);

        $statuses = collect($response->json('data'))->pluck('status')->unique();

        $statuses->each(function (string $status) {
            $this->assertContains($status, ['pending', 'preparing', 'ready']);
        });

        $this->assertFalse($statuses->contains('served'));
        $this->assertFalse($statuses->contains('canceled'));
    }

    // ─── TC24 ───────────────────────────────────────────────────────────────

    /**
     * TC24: Staff can cancel a pending order.
     * Pending → Canceled is the only valid cancel transition.
     */
    public function test_staff_can_cancel_pending_order(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/orders/{$order->id}/status", [
                'status' => 'canceled',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'canceled');

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'canceled',
        ]);
    }

    // ─── TC25 ───────────────────────────────────────────────────────────────

    /**
     * TC25: Staff can advance an order from preparing → ready.
     */
    public function test_staff_can_advance_order_from_preparing_to_ready(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->preparingOrder();

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/orders/{$order->id}/status", [
                'status' => 'ready',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ready');
    }

    // ─── TC26 ───────────────────────────────────────────────────────────────

    /**
     * TC26: Staff can advance an order from ready → served.
     */
    public function test_staff_can_advance_order_from_ready_to_served(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->readyOrder();

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/orders/{$order->id}/status", [
                'status' => 'served',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'served');
    }

    // ─── TC27 ───────────────────────────────────────────────────────────────

    /**
     * TC27: An invalid status transition is rejected with 422.
     * Pending → Served skips the linear pipeline and must be refused.
     */
    public function test_staff_cannot_make_invalid_status_transition(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        $this->actingAs($staff, 'sanctum')
            ->patchJson("/api/kitchen/orders/{$order->id}/status", [
                'status' => 'served', // not in allowed transitions for 'pending'
            ])
            ->assertStatus(422);

        // Order status must be unchanged
        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'pending',
        ]);
    }
}
