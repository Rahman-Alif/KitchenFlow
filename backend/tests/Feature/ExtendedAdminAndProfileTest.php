<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * TC051 – TC064
 * Covers: Profile (view/update/password), Password Reset Confirm,
 * Tenant details, Low Stock, Stock History, Manual Sale, Audit Trail,
 * Roles, Users-with-Roles, Bulk Import, Message Delete.
 */
class ExtendedAdminAndProfileTest extends TestCase
{
    // ─── TC051 ───────────────────────────────────────────────────────────────

    /**
     * TC051: Any authenticated user can view their own profile.
     */
    public function test_authenticated_user_can_view_own_profile(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.email', $admin->email)
            ->assertJsonPath('data.role', 'admin');
    }

    // ─── TC052 ───────────────────────────────────────────────────────────────

    /**
     * TC052: Authenticated user can update their own name.
     */
    public function test_authenticated_user_can_update_own_profile(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => 'Sarah Ahmed-Khan',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id'   => $admin->id,
            'name' => 'Sarah Ahmed-Khan',
        ]);
    }

    // ─── TC053 ───────────────────────────────────────────────────────────────

    /**
     * TC053: Authenticated user can change their own password.
     * After change, the new password must work at login.
     */
    public function test_authenticated_user_can_change_own_password(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password'      => 'password',
                'password'              => 'NewPass456!',
                'password_confirmation' => 'NewPass456!',
            ])
            ->assertStatus(200);

        // Old password must fail
        $this->postJson('/api/auth/login', [
            'email'    => $customer->email,
            'password' => 'password',
        ])->assertStatus(422);

        // New password must succeed
        $this->postJson('/api/auth/login', [
            'email'    => $customer->email,
            'password' => 'NewPass456!',
        ])->assertStatus(200);
    }

    // ─── TC054 ───────────────────────────────────────────────────────────────

    /**
     * TC054: A valid reset token allows the user to set a new password.
     * The old password must stop working after the reset.
     */
    public function test_password_reset_confirm_sets_new_password(): void
    {
        $customer = $this->customer();

        // Create a fresh valid token
        $token = \Illuminate\Support\Str::random(64);
        \App\Models\PasswordResetToken::create([
            'user_id'    => $customer->id,
            'token'      => $token,
            'expires_at' => now()->addHour(),
        ]);

        $this->postJson('/api/auth/password-reset/confirm', [
            'token'                 => $token,
            'password'              => 'ResetPass123!',
            'password_confirmation' => 'ResetPass123!',
        ])->assertStatus(200);

        // Old password must fail
        $this->postJson('/api/auth/login', [
            'email'    => $customer->email,
            'password' => 'password',
        ])->assertStatus(422);

        // New password must work
        $this->postJson('/api/auth/login', [
            'email'    => $customer->email,
            'password' => 'ResetPass123!',
        ])->assertStatus(200);
    }

    // ─── TC055 ───────────────────────────────────────────────────────────────

    /**
     * TC055: Admin can retrieve their tenant's subscription details.
     */
    public function test_admin_can_view_tenant_subscription_details(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/tenant')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['name', 'subscription_active', 'subscription_ends_at'],
            ])
            ->assertJsonPath('data.subscription_active', true);
    }

    // ─── TC056 ───────────────────────────────────────────────────────────────

    /**
     * TC056: Admin can retrieve items at or below their low stock threshold.
     * Items above their threshold must not appear.
     */
    public function test_admin_can_view_low_stock_items(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/menu-items/low-stock');

        $response->assertStatus(200);

        $this->assertNotEmpty($response->json('data'));

        // Every returned item must have stock at or below its threshold
        collect($response->json('data'))->each(function (array $item) {
            $this->assertLessThanOrEqual(
                $item['low_stock_threshold'],
                $item['stock_quantity']
            );
        });
    }

    // ─── TC057 ───────────────────────────────────────────────────────────────

    /**
     * TC057: Admin can view the full stock movement history for a menu item.
     */
    public function test_admin_can_view_stock_history_for_item(): void
    {
        $admin = $this->admin();
        $item  = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/menu-items/{$item->id}/stock-history");

        $response->assertStatus(200);

        $movements = collect($response->json('data.movements'));

        $this->assertNotEmpty($movements);

        $movements->each(function (array $movement) {
            $this->assertArrayHasKey('type', $movement);
            $this->assertArrayHasKey('quantity_changed', $movement);
            $this->assertArrayHasKey('created_at', $movement);
        });
    }

    // ─── TC058 ───────────────────────────────────────────────────────────────

    /**
     * TC058: Admin can manually record a sale against a menu item,
     * which decrements stock and logs a 'sale' movement.
     */
    public function test_admin_can_manually_record_sale(): void
    {
        $admin       = $this->admin();
        $item        = MenuItem::where('name', 'Vegetable Samosa (2 pcs)')->firstOrFail();
        $stockBefore = $item->stock_quantity;

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/menu-items/{$item->id}/stock/record-sale", [
                'quantity' => 2,
            ])
            ->assertStatus(200);

        $this->assertEquals($stockBefore - 2, $item->fresh()->stock_quantity);

        // A sale movement must be logged
        $this->assertDatabaseHas('stock_movements', [
            'movement_type'   => 'sale',
            'quantity_changed' => -2,
        ]);
    }

    // ─── TC059 ───────────────────────────────────────────────────────────────

    /**
     * TC059: Admin can view the audit trail for a menu item,
     * which includes created_by and updated_by information.
     */
    public function test_admin_can_view_audit_trail_for_menu_item(): void
    {
        $admin = $this->admin();
        $item  = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/audit/menu-items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    // ─── TC060 ───────────────────────────────────────────────────────────────

    /**
     * TC060: Admin can retrieve the list of all system roles.
     * All three fixed roles must be present.
     */
    public function test_admin_can_list_all_roles(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/roles');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('admin'));
        $this->assertTrue($names->contains('kitchen_staff'));
        $this->assertTrue($names->contains('user'));
    }

    // ─── TC061 ───────────────────────────────────────────────────────────────

    /**
     * TC061: Admin can retrieve a single role by its ID.
     */
    public function test_admin_can_view_single_role(): void
    {
        $admin  = $this->admin();
        $roleId = \App\Models\Role::where('name', 'kitchen_staff')->value('id');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/roles/{$roleId}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'kitchen_staff');
    }

    // ─── TC062 ───────────────────────────────────────────────────────────────

    /**
     * TC062: Admin can retrieve all users with their full role details attached.
     */
    public function test_admin_can_list_users_with_role_details(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users/with-roles');

        $response->assertStatus(200);

        collect($response->json('data'))->each(function (array $user) {
            $this->assertArrayHasKey('role', $user);
        });
    }

    // ─── TC063 ───────────────────────────────────────────────────────────────

    /**
     * TC063: Admin can bulk create users by uploading a CSV file.
     * All users in the CSV must be created under the admin's tenant.
     */
    public function test_admin_can_bulk_import_users_via_csv(): void
    {
        $admin = $this->admin();

        $csvContent = implode("\n", [
            'name,email,role',
            'Bulk User One,bulk.one@nexuscorp.com,user',
            'Bulk User Two,bulk.two@nexuscorp.com,user',
            'Bulk User Three,bulk.three@nexuscorp.com,kitchen_staff',
        ]);

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvContent);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users/bulk', [
                'file' => $file,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('users', ['email' => 'bulk.one@nexuscorp.com',   'tenant_id' => $admin->tenant_id]);
        $this->assertDatabaseHas('users', ['email' => 'bulk.two@nexuscorp.com',   'tenant_id' => $admin->tenant_id]);
        $this->assertDatabaseHas('users', ['email' => 'bulk.three@nexuscorp.com', 'tenant_id' => $admin->tenant_id]);
    }

    // ─── TC064 ───────────────────────────────────────────────────────────────

    /**
     * TC064: Admin can delete a message from their inbox.
     * The message must be removed from the DB after deletion.
     */
    public function test_admin_can_delete_a_message(): void
    {
        $admin = $this->admin();

        $message = \App\Models\Message::where('receiver_id', $admin->id)
            ->orWhere('sender_id', $admin->id)
            ->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/messages/{$message->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }
}