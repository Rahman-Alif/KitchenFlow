<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Message;
use Tests\TestCase;

/**
 * TC065 – TC070
 * Covers: Kitchen Staff message send, order detail view, menu list,
 * Customer multi-item order, order with notes, Admin menu item update.
 */
class ExtendedKitchenAndCustomerTest extends TestCase
{
    // ─── TC065 ───────────────────────────────────────────────────────────────

    /**
     * TC065: Kitchen staff can send a message to the admin.
     */
    public function test_kitchen_staff_can_send_message_to_admin(): void
    {
        $staff = $this->kitchenStaff();
        $admin = $this->admin();

        $response = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/admin/messages', [
                'receiver_id' => $admin->id,
                'title'       => 'Low stock alert',
                'content'     => 'We are running low on Piyaju, please restock.',
                'tag'         => 'item_requirement',
                'priority'    => 'high',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('messages', [
            'sender_id'   => $staff->id,
            'receiver_id' => $admin->id,
            'title'       => 'Low stock alert',
        ]);
    }

    // ─── TC066 ───────────────────────────────────────────────────────────────

    /**
     * TC066: Kitchen staff can view the full detail of a single order
     * from the queue, including all items and quantities.
     */
    public function test_kitchen_staff_can_view_single_order_detail(): void
    {
        $staff = $this->kitchenStaff();
        $order = $this->pendingOrder();

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson("/api/kitchen/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonStructure([
                'data' => ['id', 'status', 'items'],
            ]);

        $this->assertNotEmpty($response->json('data.items'));
    }

    // ─── TC067 ───────────────────────────────────────────────────────────────

    /**
     * TC067: Kitchen staff can retrieve the full menu list with
     * availability and stock information for each item.
     */
    public function test_kitchen_staff_can_view_kitchen_menu_list(): void
    {
        $staff = $this->kitchenStaff();

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/kitchen/menu');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertNotEmpty($response->json('data'));

        // Each item must expose availability and stock info
        collect($response->json('data'))->each(function (array $item) {
            $this->assertArrayHasKey('is_available', $item);
            $this->assertArrayHasKey('stock_quantity', $item);
        });
    }

    // ─── TC068 ───────────────────────────────────────────────────────────────

    /**
     * TC068: Customer can place a single order containing multiple
     * distinct items. Stock must decrement correctly for each item.
     */
    public function test_customer_can_place_order_with_multiple_items(): void
    {
        $customer = $this->customer();

        $biryani = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();
        $samosa  = MenuItem::where('name', 'Vegetable Samosa (2 pcs)')->firstOrFail();

        $biryaniStockBefore = $biryani->stock_quantity;
        $samosaStockBefore  = $samosa->stock_quantity;

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [
                    ['menu_item_id' => $biryani->id, 'quantity' => 1],
                    ['menu_item_id' => $samosa->id,  'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        // Both items must appear in the order
        $items = collect($response->json('data.items'));
        $this->assertCount(2, $items);

        // Stock must decrement for each item
        $this->assertEquals($biryaniStockBefore - 1, $biryani->fresh()->stock_quantity);
        $this->assertEquals($samosaStockBefore  - 2, $samosa->fresh()->stock_quantity);
    }

    // ─── TC069 ───────────────────────────────────────────────────────────────

    /**
     * TC069: Customer can attach an optional note to their order.
     * The note must be saved and visible in the order detail.
     */
    public function test_customer_can_place_order_with_note(): void
    {
        $customer = $this->customer();
        $item     = $this->availableItem();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/user/orders', [
                'items' => [['menu_item_id' => $item->id, 'quantity' => 1]],
                'notes' => 'Please make it less spicy.',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'id'    => $response->json('data.id'),
            'notes' => 'Please make it less spicy.',
        ]);
    }

    // ─── TC070 ───────────────────────────────────────────────────────────────

    /**
     * TC070: Admin can update an existing menu item's name, description,
     * and price. Existing orders must not be affected by the price change.
     */
    public function test_admin_can_update_existing_menu_item(): void
    {
        $admin = $this->admin();
        $item  = MenuItem::where('name', 'Chicken Biryani')->firstOrFail();

        // Place an order first to snapshot the old price
        $order = $this->placeOrderAs($this->customer(), [
            ['menu_item_id' => $item->id, 'quantity' => 1],
        ]);
        $originalTotal = $order->total_amount;

        // Update the item
        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/menu-items/{$item->id}", [
                'name'        => 'Chicken Biryani Special',
                'description' => 'Premium version with extra toppings.',
                'price'       => 120.00,
                'category_id' => $item->category_id,
                'is_available' => $item->is_available,
            ])
            ->assertStatus(200);

        // Item must be updated
        $item->refresh();
        $this->assertEquals('Chicken Biryani Special', $item->name);
        $this->assertEquals('120.00', $item->price);

        // Existing order total must be unchanged (price is snapshotted)
        $order->refresh();
        $this->assertEquals($originalTotal, $order->total_amount);
    }
}