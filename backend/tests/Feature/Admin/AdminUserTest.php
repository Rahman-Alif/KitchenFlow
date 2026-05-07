<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Tests\TestCase;

/**
 * TC07 – TC10 | Admin — User Management
 *
 * Covers listing users, creating a user, toggling activation,
 * and cross-tenant data isolation.
 */
class AdminUserTest extends TestCase
{
    // ─── TC07 ───────────────────────────────────────────────────────────────

    /**
     * TC07: Admin can list all users that belong to their own tenant.
     * Orion Labs users must NOT appear in the response.
     */
    public function test_admin_can_list_own_tenant_users(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users');

        $response->assertStatus(200);

        $emails = collect($response->json('data'))->pluck('email');

        // Nexus users are present
        $this->assertTrue($emails->contains('sarah.ahmed@nexuscorp.com'));
        $this->assertTrue($emails->contains('tanvir.mahmud@nexuscorp.com'));

        // Orion users are absent
        $this->assertFalse($emails->contains('david.park@orionlabs.io'));
    }

    // ─── TC08 ───────────────────────────────────────────────────────────────

    /**
     * TC08: Admin can create a new user who is auto-assigned to the admin's tenant
     * and has is_active = true by default.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users', [
                'name'  => 'New Employee',
                'email' => 'new.employee@nexuscorp.com',
                'role'  => 'user',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'new.employee@nexuscorp.com')
            ->assertJsonPath('data.role', 'user');

        $this->assertDatabaseHas('users', [
            'email'     => 'new.employee@nexuscorp.com',
            'tenant_id' => $admin->tenant_id,
            'is_active' => true,
        ]);
    }

    // ─── TC09 ───────────────────────────────────────────────────────────────

    /**
     * TC09: Admin can deactivate an active user and then reactivate them.
     * Uses Tanvir (active by default in the seeder) as the target.
     */
    public function test_admin_can_deactivate_and_reactivate_user(): void
    {
        $admin  = $this->admin();
        $target = $this->customer(); // tanvir — is_active = true

        // Deactivate
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$target->id}/deactivate")
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);

        // Reactivate
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$target->id}/activate")
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    // ─── TC10 ───────────────────────────────────────────────────────────────

    /**
     * TC10: Cross-tenant isolation — admin cannot view or edit a user from a
     * different tenant. Orion's user must return 404 (not found in scope).
     */
    public function test_admin_cannot_access_other_tenant_user(): void
    {
        $admin      = $this->admin();
        $orionAdmin = $this->orionAdmin();

        // Attempt to view Orion's admin record as Nexus admin
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/users/{$orionAdmin->id}")
            ->assertStatus(404);

        // Attempt to deactivate Orion's admin
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$orionAdmin->id}/deactivate")
            ->assertStatus(404);
    }
}
