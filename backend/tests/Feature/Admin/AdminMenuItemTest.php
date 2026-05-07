<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\MenuItem;
use Tests\TestCase;

/**
 * TC11 – TC15 | Admin — Categories & Menu Items & Inventory
 *
 * Covers category creation, menu item creation, soft-delete,
 * availability guard on zero-stock items, and restocking.
 */
class AdminMenuItemTest extends TestCase
{
    // ─── TC11 ───────────────────────────────────────────────────────────────

    /**
     * TC11: Admin can create a category scoped to their tenant.
     */
    public function test_admin_can_create_category(): void
    {
        $admin    = $this->admin();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/categories', [
                'name' => 'Grilled Specials',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Grilled Specials');

        $this->assertDatabaseHas('categories', [
            'name'      => 'Grilled Specials',
            'tenant_id' => $admin->tenant_id,
        ]);
    }

    // ─── TC12 ───────────────────────────────────────────────────────────────

    /**
     * TC12: Admin can create a menu item under one of their categories.
     * Validates that the item appears in the DB with the correct category FK.
     */
    public function test_admin_can_create_menu_item(): void
    {
        $admin    = $this->admin();
        $category = Category::where('tenant_id', $admin->tenant_id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/menu-items', [
                'category_id'         => $category->id,
                'name'                => 'Tandoori Chicken',
                'description'         => 'Smoky clay-oven chicken',
                'price'               => 130.00,
                'stock_quantity'      => 25,
                'low_stock_threshold' => 5,
                'is_available'        => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Tandoori Chicken')
            ->assertJsonPath('data.price', '130.00');

        $this->assertDatabaseHas('menu_items', [
            'name'        => 'Tandoori Chicken',
            'category_id' => $category->id,
        ]);
    }

    // ─── TC13 ───────────────────────────────────────────────────────────────

    /**
     * TC13: Admin soft-deletes a menu item.
     * The row must remain in the DB (deleted_at set), not hard-deleted,
     * so existing order_items FKs remain intact.
     */
    public function test_admin_can_soft_delete_menu_item(): void
    {
        $admin = $this->admin();
        $item  = $this->availableItem();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/menu-items/{$item->id}")
            ->assertStatus(200);

        // Row still exists in DB (soft delete)
        $this->assertSoftDeleted('menu_items', ['id' => $item->id]);
    }

    // ─── TC14 ───────────────────────────────────────────────────────────────

    /**
     * TC14: Admin cannot enable availability on an item whose stock is 0.
     * Khichuri is seeded with stock_quantity = 0 and is_available = false.
     */
    public function test_admin_cannot_enable_availability_on_zero_stock_item(): void
    {
        $admin = $this->admin();
        $item  = MenuItem::where('name', 'Khichuri')->firstOrFail();

        $this->assertEquals(0, $item->stock_quantity);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/menu-items/{$item->id}/availability", [
                'is_available' => true,
            ])
            ->assertStatus(422);

        // Availability must remain false
        $this->assertDatabaseHas('menu_items', [
            'id'           => $item->id,
            'is_available' => false,
        ]);
    }

    // ─── TC15 ───────────────────────────────────────────────────────────────

    /**
     * TC15: Admin restocks an item.
     * Stock increments by the given quantity, is_available flips to true
     * (because stock > 0 after restock), and restock request flags are cleared.
     * Uses Khichuri which has stock = 0 and needs_restock = true.
     */
    public function test_admin_restock_increments_stock_and_clears_flags(): void
    {
        $admin = $this->admin();
        $item  = MenuItem::where('name', 'Khichuri')->firstOrFail();

        $this->assertEquals(0, $item->stock_quantity);
        $this->assertTrue($item->needs_restock);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/menu-items/{$item->id}/restock", [
                'quantity' => 20,
            ])
            ->assertStatus(200);

        $item->refresh();
        $this->assertEquals(20, $item->stock_quantity);
        $this->assertTrue($item->is_available);
        $this->assertFalse($item->needs_restock);
        $this->assertNull($item->requested_restock_quantity);
    }
}
