<?php

namespace Tests\Feature\User;

use App\Models\MenuItem;
use Tests\TestCase;

/**
 * TC34 – TC35 | Customer — Menu Browsing
 *
 * Covers that the customer menu only surfaces available items and
 * that unavailable items are hidden entirely.
 */
class CustomerMenuTest extends TestCase
{
    // ─── TC34 ───────────────────────────────────────────────────────────────

    /**
     * TC34: Customer menu endpoint returns only available items.
     * All items in the response must have is_available = true.
     */
    public function test_customer_menu_returns_only_available_items(): void
    {
        $customer = $this->customer();
        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/user/menu');

        $response->assertStatus(200);

        // Flatten all items across all categories
        $items = collect($response->json('data'))
            ->flatMap(fn($cat) => $cat['menu_items'] ?? []);

        $this->assertNotEmpty($items);

        $items->each(function (array $item) {
            $this->assertTrue(
                $item['is_available'],
                "Item '{$item['name']}' should not be visible to customers."
            );
        });
    }

    // ─── TC35 ───────────────────────────────────────────────────────────────

    /**
     * TC35: Unavailable items (zero-stock or manually disabled) are absent
     * from the customer menu.
     * Khichuri (stock 0, is_available false) and Cold Coffee (manually
     * disabled) must not appear.
     */
    public function test_unavailable_items_are_hidden_from_customer_menu(): void
    {
        $customer = $this->customer();
        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/user/menu');

        $response->assertStatus(200);

        $itemNames = collect($response->json('data'))
            ->flatMap(fn($cat) => $cat['menu_items'] ?? [])
            ->pluck('name');

        $this->assertFalse($itemNames->contains('Khichuri'));
        $this->assertFalse($itemNames->contains('Cold Coffee'));
    }
}
