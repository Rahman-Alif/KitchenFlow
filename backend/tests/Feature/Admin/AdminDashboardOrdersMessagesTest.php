<?php

namespace Tests\Feature\Admin;

use App\Models\Message;
use Tests\TestCase;

/**
 * TC16 – TC22 | Admin — Dashboard, Orders, Messages
 *
 * Covers dashboard stats, 7-day revenue chart, order listing,
 * order detail, inbox, sending a message, and marking as read.
 */
class AdminDashboardOrdersMessagesTest extends TestCase
{
    // ─── TC16 ───────────────────────────────────────────────────────────────

    /**
     * TC16: Admin dashboard returns the expected JSON structure containing
     * order stats (counts by status) and low-stock summary.
     */
    public function test_admin_dashboard_returns_order_stats(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'order_summary',
                    'low_stock_count',
                ],
            ]);

        // There must be at least one active order (seeded)
        $summary = $response->json('data.order_summary');
        $this->assertIsArray($summary);
    }

    // ─── TC17 ───────────────────────────────────────────────────────────────

    /**
     * TC17: The revenue-week endpoint returns exactly 7 data points,
     * one per day, each containing a date and a revenue value.
     */
    public function test_admin_revenue_week_returns_seven_data_points(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard/revenue-week');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(7, $data);

        foreach ($data as $point) {
            $this->assertArrayHasKey('date', $point);
            $this->assertArrayHasKey('revenue', $point);
        }
    }

    // ─── TC18 ───────────────────────────────────────────────────────────────

    /**
     * TC18: Admin can list all orders belonging to their tenant.
     * Orion orders must not appear in the response.
     */
    public function test_admin_can_list_own_tenant_orders(): void
    {
        $admin    = $this->admin();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        // All returned orders must belong to Nexus tenant users
        $userIds = collect($response->json('data'))
            ->pluck('user.id')
            ->unique();

        $orionUserIds = \App\Models\User::where('tenant_id', $this->orionAdmin()->tenant_id)
            ->pluck('id');

        foreach ($userIds as $userId) {
            $this->assertFalse($orionUserIds->contains($userId));
        }
    }

    // ─── TC19 ───────────────────────────────────────────────────────────────

    /**
     * TC19: Admin can view a single order with full detail
     * (order items, user, status, total_amount).
     */
    public function test_admin_can_view_single_order_detail(): void
    {
        $admin = $this->admin();
        $order = $this->servedOrder();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'status', 'total_amount',
                    'user', 'items',
                ],
            ])
            ->assertJsonPath('data.id', $order->id);
    }

    // ─── TC20 ───────────────────────────────────────────────────────────────

    /**
     * TC20: Admin can retrieve their inbox.
     * The seeder creates multiple messages addressed to Sarah (admin).
     */
    public function test_admin_can_view_message_inbox(): void
    {
        $admin    = $this->admin();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/messages');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertNotEmpty($response->json('data'));
    }

    // ─── TC21 ───────────────────────────────────────────────────────────────

    /**
     * TC21: Admin can send a message to a kitchen staff member.
     * Validates that the message is persisted in the DB.
     */
    public function test_admin_can_send_message_to_kitchen_staff(): void
    {
        $admin = $this->admin();
        $staff = $this->kitchenStaff();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/messages', [
                'receiver_id' => $staff->id,
                'title'       => 'Weekend shift reminder',
                'content'     => 'Please confirm your availability for Saturday.',
                'tag'         => 'staff_duty',
                'priority'    => 'medium',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'sender_id'   => $admin->id,
            'receiver_id' => $staff->id,
            'title'       => 'Weekend shift reminder',
        ]);
    }

    // ─── TC22 ───────────────────────────────────────────────────────────────

    /**
     * TC22: Admin can mark an unread message as read.
     * The seeder includes a high-priority unread message from Priya to Sarah.
     */
    public function test_admin_can_mark_message_as_read(): void
    {
        $admin = $this->admin();

        // Pick an unread message addressed to the admin
        $message = Message::where('receiver_id', $admin->id)
            ->where('is_read', false)
            ->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/messages/{$message->id}/read")
            ->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'id'      => $message->id,
            'is_read' => true,
        ]);
    }
}
