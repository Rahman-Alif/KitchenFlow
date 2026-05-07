<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * TC46 – TC50 | Role Boundary & Security
 *
 * Covers role enforcement across all three roles:
 * - Customers blocked from admin endpoints
 * - Kitchen staff blocked from admin endpoints
 * - Admin blocked from kitchen staff endpoints
 * - Unauthenticated requests blocked globally
 * - Inactive tenant user blocked after subscription lapse
 */
class RoleBoundaryTest extends TestCase
{
    // ─── TC46 ───────────────────────────────────────────────────────────────

    /**
     * TC46: A customer (role = 'user') is forbidden from accessing
     * any admin-only endpoint. Tests the EnsureRole middleware.
     */
    public function test_customer_cannot_access_admin_endpoints(): void
    {
        $customer = $this->customer();

        // Admin dashboard
        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);

        // Admin user list (admin-only, not the shared list)
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/users', [
                'name' => 'Test', 'email' => 'x@x.com', 'role' => 'user',
            ])
            ->assertStatus(403);

        // Admin menu item creation
        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/admin/menu-items', [])
            ->assertStatus(403);
    }

    // ─── TC47 ───────────────────────────────────────────────────────────────

    /**
     * TC47: Kitchen staff (role = 'kitchen_staff') is forbidden from
     * admin-only endpoints such as user management and menu item creation.
     */
    public function test_kitchen_staff_cannot_access_admin_only_endpoints(): void
    {
        $staff = $this->kitchenStaff();

        $this->actingAs($staff, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/admin/users', [
                'name' => 'Test', 'email' => 'x@x.com', 'role' => 'user',
            ])
            ->assertStatus(403);

        $this->actingAs($staff, 'sanctum')
            ->deleteJson('/api/admin/menu-items/1')
            ->assertStatus(403);
    }

    // ─── TC48 ───────────────────────────────────────────────────────────────

    /**
     * TC48: Admin (role = 'admin') is forbidden from kitchen-staff-only
     * endpoints like the order queue and availability toggle.
     * Admins have a separate order read endpoint; the kitchen queue is
     * scoped exclusively to kitchen_staff.
     */
    public function test_admin_cannot_access_kitchen_staff_endpoints(): void
    {
        $admin = $this->admin();

        // Kitchen order queue
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/kitchen/orders')
            ->assertStatus(403);

        // Kitchen availability toggle
        $item = $this->availableItem();
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/kitchen/menu/{$item->id}/availability", [
                'is_available' => false,
            ])
            ->assertStatus(403);
    }

    // ─── TC49 ───────────────────────────────────────────────────────────────

    /**
     * TC49: Any protected endpoint must return 401 for unauthenticated requests.
     * No Bearer token is sent.
     */
    public function test_unauthenticated_request_is_rejected_with_401(): void
    {
        $this->getJson('/api/admin/dashboard')->assertStatus(401);
        $this->getJson('/api/kitchen/orders')->assertStatus(401);
        $this->getJson('/api/user/menu')->assertStatus(401);
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    // ─── TC50 ───────────────────────────────────────────────────────────────

    /**
     * TC50: A user whose tenant subscription has expired is blocked from
     * all protected endpoints by CheckTenantSubscription middleware.
     * We activate Orion's subscription temporarily to obtain a valid token,
     * then revert the subscription and verify subsequent requests are blocked.
     *
     * This simulates a mid-session subscription lapse: the token exists but
     * the tenant check fails on the next request.
     */
    public function test_expired_tenant_subscription_blocks_all_protected_endpoints(): void
    {
        $orionAdmin = $this->orionAdmin();

        // Temporarily make the subscription active so login succeeds
        $orionAdmin->tenant->update([
            'subscription_active'  => true,
            'subscription_ends_at' => now()->addDay(),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $orionAdmin->email,
            'password' => 'password',
        ]);
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');

        // Now expire the subscription mid-session
        $orionAdmin->tenant->update([
            'subscription_active'  => false,
            'subscription_ends_at' => now()->subDay(),
        ]);

        // Protected endpoints must now be blocked
        $this->withToken($token)
            ->getJson('/api/admin/dashboard')
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/user/menu')
            ->assertStatus(403);
    }
}
