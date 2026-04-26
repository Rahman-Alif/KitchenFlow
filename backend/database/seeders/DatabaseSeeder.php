<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * KitchenFlow — Master Database Seeder
 * Simulates ~2 weeks of realistic catering operations across 2 tenants:
 *   - Tenant 1 (Nexus Corp)  → active subscription, full team, busy canteen
 *   - Tenant 2 (Orion Labs)  → lapsed/inactive subscription, minimal data
 *
 * Run: docker exec -it kitchenflow_backend php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    // ─── Shared time anchor ───────────────────────────────────────────────────
    private Carbon $now;
    private Carbon $twoWeeksAgo;

    public function run(): void
    {
        $this->now         = Carbon::now();
        $this->twoWeeksAgo = Carbon::now()->subDays(14);

        // Insertion order respects FK constraints
        $this->seedTenants();
        $this->seedUsers();
        $this->seedPasswordResetTokens();
        $this->seedCategories();
        $this->seedMenuItems();
        $this->seedOrders();       // also inserts order_items
        $this->seedTransactions(); // only for 'served' orders
        $this->seedMessages();
    }

    // =========================================================================
    // TENANTS
    // =========================================================================
    private function seedTenants(): void
    {
        DB::table('tenants')->insert([
            // Active tenant — healthy subscription
            [
                'id'                   => 1,
                'name'                 => 'Nexus Corp',
                'subscription_active'  => true,
                'subscription_ends_at' => $this->now->copy()->addMonths(5),
                'created_at'           => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'           => $this->twoWeeksAgo->copy()->subDays(30),
            ],
            // Inactive tenant — subscription lapsed, kept for historical data
            [
                'id'                   => 2,
                'name'                 => 'Orion Labs',
                'subscription_active'  => false,
                'subscription_ends_at' => $this->now->copy()->subDays(3),
                'created_at'           => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at'           => $this->now->copy()->subDays(3),
            ],
        ]);
    }

    // =========================================================================
    // USERS
    // Nexus Corp: 1 admin, 2 kitchen_staff, 8 users (1 suspended, 1 soft-deleted)
    // Orion Labs: 1 admin, 1 kitchen_staff, 2 users (minimal — lapsed org)
    // =========================================================================
    private function seedUsers(): void
    {
        $defaultPassword = Hash::make('password');

        DB::table('users')->insert([

            // ── Nexus Corp ────────────────────────────────────────────────────
            [   // id: 1
                'tenant_id'  => 1,
                'name'       => 'Sarah Ahmed',
                'email'      => 'sarah.ahmed@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
                'deleted_at' => null,
            ],
            [   // id: 2 — kitchen staff A
                'tenant_id'  => 1,
                'name'       => 'Karim Hossain',
                'email'      => 'karim.hossain@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'kitchen_staff',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
                'deleted_at' => null,
            ],
            [   // id: 3 — kitchen staff B (newer hire)
                'tenant_id'  => 1,
                'name'       => 'Priya Nair',
                'email'      => 'priya.nair@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'kitchen_staff',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(5),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(5),
                'deleted_at' => null,
            ],
            [   // id: 4 — regular user
                'tenant_id'  => 1,
                'name'       => 'Tanvir Mahmud',
                'email'      => 'tanvir.mahmud@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(28),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(28),
                'deleted_at' => null,
            ],
            [   // id: 5 — regular user
                'tenant_id'  => 1,
                'name'       => 'Nusrat Jahan',
                'email'      => 'nusrat.jahan@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(28),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(28),
                'deleted_at' => null,
            ],
            [   // id: 6 — regular user
                'tenant_id'  => 1,
                'name'       => 'Arif Chowdhury',
                'email'      => 'arif.chowdhury@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(20),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(20),
                'deleted_at' => null,
            ],
            [   // id: 7 — regular user
                'tenant_id'  => 1,
                'name'       => 'Mehrin Sultana',
                'email'      => 'mehrin.sultana@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(20),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(20),
                'deleted_at' => null,
            ],
            [   // id: 8 — suspended user (is_active = false)
                'tenant_id'  => 1,
                'name'       => 'Rafiq Islam',
                'email'      => 'rafiq.islam@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => false,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(25),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(2),
                'deleted_at' => null,
            ],
            [   // id: 9 — regular user
                'tenant_id'  => 1,
                'name'       => 'Shabnam Akter',
                'email'      => 'shabnam.akter@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(15),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(15),
                'deleted_at' => null,
            ],
            [   // id: 10 — soft-deleted user (left the company)
                'tenant_id'  => 1,
                'name'       => 'Zahid Hassan',
                'email'      => 'zahid.hassan@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => false,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(8),
                'deleted_at' => $this->twoWeeksAgo->copy()->subDays(8), // soft deleted
            ],
            [   // id: 11 — regular user
                'tenant_id'  => 1,
                'name'       => 'Fariha Begum',
                'email'      => 'fariha.begum@nexuscorp.com',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(10),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(10),
                'deleted_at' => null,
            ],

            // ── Orion Labs (inactive tenant) ──────────────────────────────────
            [   // id: 12
                'tenant_id'  => 2,
                'name'       => 'David Park',
                'email'      => 'david.park@orionlabs.io',
                'password'   => $defaultPassword,
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at' => null,
            ],
            [   // id: 13
                'tenant_id'  => 2,
                'name'       => 'Lena Müller',
                'email'      => 'lena.muller@orionlabs.io',
                'password'   => $defaultPassword,
                'role'       => 'kitchen_staff',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at' => null,
            ],
            [   // id: 14
                'tenant_id'  => 2,
                'name'       => 'Reza Moradi',
                'email'      => 'reza.moradi@orionlabs.io',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at' => null,
            ],
            [   // id: 15
                'tenant_id'  => 2,
                'name'       => 'Yuki Tanaka',
                'email'      => 'yuki.tanaka@orionlabs.io',
                'password'   => $defaultPassword,
                'role'       => 'user',
                'is_active'  => true,
                'created_at' => $this->twoWeeksAgo->copy()->subDays(55),
                'updated_at' => $this->twoWeeksAgo->copy()->subDays(55),
                'deleted_at' => null,
            ],
        ]);
    }

    // =========================================================================
    // PASSWORD RESET TOKENS
    // One pending (not yet used), one expired
    // =========================================================================
    private function seedPasswordResetTokens(): void
    {
        DB::table('password_reset_tokens')->insert([
            [
                // User Nusrat requested a reset — still valid
                'user_id'    => 5,
                'token'      => bin2hex(random_bytes(32)),
                'expires_at' => $this->now->copy()->addMinutes(45),
                'created_at' => $this->now->copy()->subMinutes(15),
            ],
            [
                // User Arif requested a reset — already expired (never used)
                'user_id'    => 6,
                'token'      => bin2hex(random_bytes(32)),
                'expires_at' => $this->now->copy()->subHours(2),
                'created_at' => $this->now->copy()->subHours(3),
            ],
        ]);
    }

    // =========================================================================
    // CATEGORIES
    // Nexus Corp: 5 categories (1 soft-deleted — "Seasonal Specials" retired)
    // Orion Labs: 2 categories (minimal)
    // =========================================================================
    private function seedCategories(): void
    {
        DB::table('categories')->insert([
            // ── Nexus Corp ────────────────────────────────────────────────────
            ['id' => 1,  'tenant_id' => 1, 'name' => 'Rice & Curry',     'created_at' => $this->twoWeeksAgo->copy()->subDays(30), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(30), 'deleted_at' => null],
            ['id' => 2,  'tenant_id' => 1, 'name' => 'Snacks & Starters','created_at' => $this->twoWeeksAgo->copy()->subDays(30), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(30), 'deleted_at' => null],
            ['id' => 3,  'tenant_id' => 1, 'name' => 'Beverages',        'created_at' => $this->twoWeeksAgo->copy()->subDays(30), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(30), 'deleted_at' => null],
            ['id' => 4,  'tenant_id' => 1, 'name' => 'Desserts',         'created_at' => $this->twoWeeksAgo->copy()->subDays(30), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(30), 'deleted_at' => null],
            ['id' => 5,  'tenant_id' => 1, 'name' => 'Seasonal Specials','created_at' => $this->twoWeeksAgo->copy()->subDays(30), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(12), 'deleted_at' => $this->twoWeeksAgo->copy()->subDays(12)], // soft-deleted

            // ── Orion Labs ────────────────────────────────────────────────────
            ['id' => 6,  'tenant_id' => 2, 'name' => 'Mains',            'created_at' => $this->twoWeeksAgo->copy()->subDays(60), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(60), 'deleted_at' => null],
            ['id' => 7,  'tenant_id' => 2, 'name' => 'Drinks',           'created_at' => $this->twoWeeksAgo->copy()->subDays(60), 'updated_at' => $this->twoWeeksAgo->copy()->subDays(60), 'deleted_at' => null],
        ]);
    }

    // =========================================================================
    // MENU ITEMS
    // Nexus Corp: 14 items across 5 categories (varied states)
    // Orion Labs: 3 items
    //
    // IMAGE FILENAMES (save these to storage/app/public/menu/):
    //   nexus_chicken_biryani.jpg
    //   nexus_beef_kala_bhuna.jpg
    //   nexus_dal_bhat.jpg
    //   nexus_khichuri.jpg
    //   nexus_vegetable_curry.jpg
    //   nexus_samosa.jpg
    //   nexus_spring_roll.jpg
    //   nexus_piyaju.jpg
    //   nexus_lemon_tea.jpg
    //   nexus_mango_lassi.jpg
    //   nexus_cold_coffee.jpg
    //   nexus_mishti_doi.jpg
    //   nexus_rasgolla.jpg
    //   nexus_mango_pudding.jpg   ← belongs to soft-deleted "Seasonal Specials" category
    //   orion_pasta_bolognese.jpg
    //   orion_grilled_chicken.jpg
    //   orion_mineral_water.jpg
    // =========================================================================
    private function seedMenuItems(): void
    {
        DB::table('menu_items')->insert([

            // ── Category 1: Rice & Curry (Nexus Corp) ─────────────────────────
            [
                'id'                       => 1,
                'category_id'              => 1,
                'name'                     => 'Chicken Biryani',
                'description'              => 'Aromatic basmati rice slow-cooked with tender chicken, saffron, and whole spices. Served with raita and sliced lemon.',
                'image_path'               => 'storage/menu/nexus_chicken_biryani.jpg',
                'price'                    => '120.00',
                'stock_quantity'           => 35,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->now->copy()->subHours(2),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 2,
                'category_id'              => 1,
                'name'                     => 'Beef Kala Bhuna',
                'description'              => 'Slow-roasted Chittagonian-style beef cooked in a deep, dark gravy of caramelised onions, whole spices, and mustard oil. Best paired with white rice.',
                'image_path'               => 'storage/menu/nexus_beef_kala_bhuna.jpg',
                'price'                    => '150.00',
                'stock_quantity'           => 8,
                'low_stock_threshold'      => 10,
                'needs_restock'            => true,
                'requested_restock_quantity' => 30,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->now->copy()->subHours(1),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 3,
                'category_id'              => 1,
                'name'                     => 'Dal Bhat',
                'description'              => 'Classic Bengali comfort meal — steamed white rice served with a generous portion of turmeric-lentil dal, fried hilsa or aloo bhaji on the side.',
                'image_path'               => 'storage/menu/nexus_dal_bhat.jpg',
                'price'                    => '70.00',
                'stock_quantity'           => 50,
                'low_stock_threshold'      => 15,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 4,
                'category_id'              => 1,
                'name'                     => 'Khichuri',
                'description'              => 'Warm rice and lentil porridge seasoned with turmeric, cumin, and bay leaves, topped with ghee. A popular rainy-day special served with fried eggplant.',
                'image_path'               => 'storage/menu/nexus_khichuri.jpg',
                'price'                    => '80.00',
                'stock_quantity'           => 0,
                'low_stock_threshold'      => 10,
                'needs_restock'            => true,
                'requested_restock_quantity' => 20,
                'is_available'             => false,  // auto-disabled at zero stock
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->now->copy()->subHours(3),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 5,
                'category_id'              => 1,
                'name'                     => 'Vegetable Curry Set',
                'description'              => 'Seasonal vegetables simmered in a light tomato-based curry, served with two pieces of soft paratha. Suitable for vegetarians.',
                'image_path'               => 'storage/menu/nexus_vegetable_curry.jpg',
                'price'                    => '65.00',
                'stock_quantity'           => 22,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'deleted_at'               => null,
            ],

            // ── Category 2: Snacks & Starters (Nexus Corp) ────────────────────
            [
                'id'                       => 6,
                'category_id'              => 2,
                'name'                     => 'Vegetable Samosa (2 pcs)',
                'description'              => 'Crispy golden pastry shells filled with spiced potatoes, peas, and herbs. Served with mint-coriander chutney.',
                'image_path'               => 'storage/menu/nexus_samosa.jpg',
                'price'                    => '30.00',
                'stock_quantity'           => 60,
                'low_stock_threshold'      => 20,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 7,
                'category_id'              => 2,
                'name'                     => 'Chicken Spring Roll (2 pcs)',
                'description'              => 'Crunchy rolls filled with shredded chicken, cabbage, carrots, and sesame-soy sauce. Served hot with sweet chili dip.',
                'image_path'               => 'storage/menu/nexus_spring_roll.jpg',
                'price'                    => '45.00',
                'stock_quantity'           => 40,
                'low_stock_threshold'      => 15,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(25),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(25),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 8,
                'category_id'              => 2,
                'name'                     => 'Piyaju (4 pcs)',
                'description'              => 'Traditional lentil fritters mixed with onion, green chilli, and fresh coriander, fried to a light crisp. A Dhaka canteen staple.',
                'image_path'               => 'storage/menu/nexus_piyaju.jpg',
                'price'                    => '20.00',
                'stock_quantity'           => 5,
                'low_stock_threshold'      => 15,
                'needs_restock'            => true,
                'requested_restock_quantity' => 40,
                'is_available'             => true,  // still available, just low
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->now->copy()->subMinutes(30),
                'deleted_at'               => null,
            ],

            // ── Category 3: Beverages (Nexus Corp) ────────────────────────────
            [
                'id'                       => 9,
                'category_id'              => 3,
                'name'                     => 'Lemon Tea',
                'description'              => 'Freshly brewed black tea with a squeeze of lemon, lightly sweetened. Served hot in a glass.',
                'image_path'               => 'storage/menu/nexus_lemon_tea.jpg',
                'price'                    => '15.00',
                'stock_quantity'           => 100,
                'low_stock_threshold'      => 25,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 10,
                'category_id'              => 3,
                'name'                     => 'Mango Lassi',
                'description'              => 'Thick and chilled yoghurt drink blended with ripe Alphonso mango pulp, a pinch of cardamom, and crushed ice.',
                'image_path'               => 'storage/menu/nexus_mango_lassi.jpg',
                'price'                    => '40.00',
                'stock_quantity'           => 28,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(20),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(20),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 11,
                'category_id'              => 3,
                'name'                     => 'Cold Coffee',
                'description'              => 'Blended iced coffee with full-cream milk, sugar, and a dash of vanilla essence. A canteen favourite on warm afternoons.',
                'image_path'               => 'storage/menu/nexus_cold_coffee.jpg',
                'price'                    => '50.00',
                'stock_quantity'           => 18,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => false, // manually disabled by kitchen staff today
                'created_at'              => $this->twoWeeksAgo->copy()->subDays(18),
                'updated_at'               => $this->now->copy()->subHours(1),
                'deleted_at'               => null,
            ],

            // ── Category 4: Desserts (Nexus Corp) ─────────────────────────────
            [
                'id'                       => 12,
                'category_id'              => 4,
                'name'                     => 'Mishti Doi',
                'description'              => 'Traditional Bengali sweet yoghurt, gently fermented and set in earthen pots. Mildly sweet with a slightly caramelised top.',
                'image_path'               => 'storage/menu/nexus_mishti_doi.jpg',
                'price'                    => '35.00',
                'stock_quantity'           => 30,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 13,
                'category_id'              => 4,
                'name'                     => 'Rasgolla (2 pcs)',
                'description'              => 'Soft, spongy cottage cheese balls soaked in light sugar syrup. A classic Bengali sweet that melts in the mouth.',
                'image_path'               => 'storage/menu/nexus_rasgolla.jpg',
                'price'                    => '25.00',
                'stock_quantity'           => 45,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(28),
                'deleted_at'               => null,
            ],

            // ── Category 5: Seasonal Specials (SOFT-DELETED category — item archived too) ──
            [
                'id'                       => 14,
                'category_id'              => 5,
                'name'                     => 'Mango Pudding',
                'description'              => 'Chilled pudding made from fresh seasonal mango pulp, condensed milk, and agar. Available during mango season only.',
                'image_path'               => 'storage/menu/nexus_mango_pudding.jpg',
                'price'                    => '55.00',
                'stock_quantity'           => 0,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => false,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(30),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(12),
                'deleted_at'               => $this->twoWeeksAgo->copy()->subDays(12), // soft-deleted with category
            ],

            // ── Orion Labs items ───────────────────────────────────────────────
            [
                'id'                       => 15,
                'category_id'              => 6,
                'name'                     => 'Pasta Bolognese',
                'description'              => 'Al dente penne tossed in a rich beef and tomato ragù, slow-cooked with garlic, red wine, and fresh basil. Finished with parmesan.',
                'image_path'               => 'storage/menu/orion_pasta_bolognese.jpg',
                'price'                    => '180.00',
                'stock_quantity'           => 12,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 16,
                'category_id'              => 6,
                'name'                     => 'Grilled Chicken Plate',
                'description'              => 'Marinated chicken breast grilled to order, served with roasted potatoes and garden salad.',
                'image_path'               => 'storage/menu/orion_grilled_chicken.jpg',
                'price'                    => '200.00',
                'stock_quantity'           => 9,
                'low_stock_threshold'      => 10,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at'               => null,
            ],
            [
                'id'                       => 17,
                'category_id'              => 7,
                'name'                     => 'Mineral Water (500ml)',
                'description'              => 'Chilled bottled mineral water.',
                'image_path'               => 'storage/menu/orion_mineral_water.jpg',
                'price'                    => '20.00',
                'stock_quantity'           => 80,
                'low_stock_threshold'      => 20,
                'needs_restock'            => false,
                'requested_restock_quantity' => null,
                'is_available'             => true,
                'created_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'updated_at'               => $this->twoWeeksAgo->copy()->subDays(60),
                'deleted_at'               => null,
            ],
        ]);
    }

    // =========================================================================
    // ORDERS + ORDER ITEMS
    // ~30 orders spanning 14 days across 5 Nexus Corp users
    // Mix of: served (with transactions), pending, preparing, ready, canceled
    // Orion Labs: 3 historical orders (pre-lapse)
    // =========================================================================
    private function seedOrders(): void
    {
        // Each entry: [user_id, status, notes, days_ago, items => [[menu_item_id, qty, unit_price]]]
        $orderBlueprints = [

            // ── Day 14 (oldest) ───────────────────────────────────────────────
            [4,  'served',    null,                         14, [[1,1,'120.00'],[9,1,'15.00']]],
            [5,  'served',    null,                         14, [[3,1,'70.00'],[6,2,'30.00']]],
            [6,  'canceled',  null,                         14, [[2,1,'150.00']]],

            // ── Day 13 ────────────────────────────────────────────────────────
            [4,  'served',    null,                         13, [[1,1,'120.00'],[12,1,'35.00']]],
            [7,  'served',    null,                         13, [[5,1,'65.00'],[9,2,'15.00']]],
            [9,  'served',    'Extra spicy please',         13, [[2,1,'150.00'],[6,1,'30.00']]],

            // ── Day 12 (day Seasonal Specials was retired) ────────────────────
            [5,  'served',    null,                         12, [[3,2,'70.00'],[13,1,'25.00']]],
            [11, 'served',    null,                         12, [[1,1,'120.00'],[10,1,'40.00']]],

            // ── Day 11 ────────────────────────────────────────────────────────
            [4,  'served',    null,                         11, [[7,2,'45.00'],[9,1,'15.00']]],
            [6,  'served',    null,                         11, [[1,1,'120.00'],[12,1,'35.00']]],
            [7,  'canceled',  null,                         11, [[5,1,'65.00'],[8,2,'20.00']]],

            // ── Day 10 ────────────────────────────────────────────────────────
            [9,  'served',    null,                         10, [[3,1,'70.00'],[6,2,'30.00'],[9,1,'15.00']]],
            [5,  'served',    null,                         10, [[2,1,'150.00'],[13,2,'25.00']]],

            // ── Day 9 ─────────────────────────────────────────────────────────
            [4,  'served',    'No onion in curry',           9, [[1,1,'120.00'],[9,1,'15.00']]],
            [11, 'served',    null,                          9, [[5,1,'65.00'],[6,1,'30.00'],[12,1,'35.00']]],
            [6,  'served',    null,                          9, [[7,1,'45.00'],[10,1,'40.00']]],

            // ── Day 8 (Zahid soft-deleted this day) ──────────────────────────
            [10, 'served',    null,                          8, [[3,1,'70.00'],[8,2,'20.00']]],   // Zahid's last order before deletion
            [7,  'served',    null,                          8, [[1,1,'120.00'],[9,1,'15.00']]],

            // ── Day 7 ─────────────────────────────────────────────────────────
            [4,  'served',    null,                          7, [[2,1,'150.00'],[13,1,'25.00']]],
            [5,  'served',    null,                          7, [[1,1,'120.00'],[10,1,'40.00'],[9,1,'15.00']]],
            [9,  'canceled',  null,                          7, [[7,2,'45.00']]],

            // ── Day 6 ─────────────────────────────────────────────────────────
            [11, 'served',    null,                          6, [[3,1,'70.00'],[6,2,'30.00']]],
            [6,  'served',    'Less salt please',            6, [[1,1,'120.00'],[12,1,'35.00']]],

            // ── Day 5 ─────────────────────────────────────────────────────────
            [4,  'served',    null,                          5, [[5,1,'65.00'],[9,2,'15.00'],[13,1,'25.00']]],
            [7,  'served',    null,                          5, [[2,1,'150.00'],[10,1,'40.00']]],

            // ── Day 4 ─────────────────────────────────────────────────────────
            [5,  'served',    null,                          4, [[1,1,'120.00'],[6,2,'30.00']]],
            [9,  'served',    null,                          4, [[3,1,'70.00'],[9,1,'15.00'],[12,1,'35.00']]],
            [11, 'served',    null,                          4, [[7,2,'45.00'],[13,1,'25.00']]],

            // ── Day 3 ─────────────────────────────────────────────────────────
            [4,  'served',    'Extra rice',                  3, [[1,2,'120.00'],[9,1,'15.00']]],
            [6,  'served',    null,                          3, [[2,1,'150.00'],[10,1,'40.00']]],

            // ── Day 2 ─────────────────────────────────────────────────────────
            [5,  'served',    null,                          2, [[5,1,'65.00'],[6,1,'30.00'],[9,1,'15.00']]],
            [7,  'served',    null,                          2, [[1,1,'120.00'],[12,1,'35.00'],[13,1,'25.00']]],

            // ── Today — active queue ───────────────────────────────────────────
            [4,  'served',    null,                          0, [[3,1,'70.00'],[9,1,'15.00']]],
            [9,  'ready',     null,                          0, [[1,1,'120.00'],[10,1,'40.00']]],
            [11, 'preparing', 'Add extra chutney on side',   0, [[6,3,'30.00'],[7,1,'45.00']]],
            [5,  'preparing', null,                          0, [[2,1,'150.00'],[13,2,'25.00']]],
            [6,  'pending',   null,                          0, [[5,1,'65.00'],[8,2,'20.00']]],
            [7,  'pending',   'Mild spice only',             0, [[1,1,'120.00'],[9,2,'15.00']]],
        ];

        // ── Orion Labs historical orders (pre-lapse) ──────────────────────────
        $orionBlueprints = [
            [14, 'served', null, 20, [[15,1,'180.00'],[17,1,'20.00']]],
            [15, 'served', null, 18, [[16,1,'200.00']]],
            [14, 'served', null, 15, [[15,1,'180.00']]],
        ];

        $allBlueprints = array_merge($orderBlueprints, $orionBlueprints);

        $orderId     = 1;
        $orderItemId = 1;
        $orderRows   = [];
        $orderItemRows = [];

        // We track which orders are 'served' (so we can add transactions)
        $this->servedOrderIds = [];

        foreach ($allBlueprints as $bp) {
            [$userId, $status, $notes, $daysAgo, $items] = $bp;

            // Compute total from items
            $total = array_reduce($items, fn($carry, $item) => $carry + ($item[1] * $item[2]), 0);

            $createdAt = $daysAgo === 0
                ? $this->now->copy()->subMinutes(array_search($bp, $allBlueprints) * 8 + 5)
                : $this->now->copy()->subDays($daysAgo)->setHour(rand(11, 13))->setMinute(rand(0, 59));

            $updatedAt = $status === 'served'
                ? $createdAt->copy()->addMinutes(rand(10, 25))
                : $createdAt->copy();

            $orderRows[] = [
                'id'           => $orderId,
                'user_id'      => $userId,
                'status'       => $status,
                'total_amount' => number_format($total, 2, '.', ''),
                'notes'        => $notes,
                'created_at'   => $createdAt,
                'updated_at'   => $updatedAt,
            ];

            foreach ($items as $item) {
                [$menuItemId, $qty, $unitPrice] = $item;
                $orderItemRows[] = [
                    'id'           => $orderItemId++,
                    'order_id'     => $orderId,
                    'menu_item_id' => $menuItemId,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ];
            }

            if ($status === 'served') {
                $this->servedOrderIds[] = [
                    'order_id'     => $orderId,
                    'total_amount' => (float) number_format($total, 2, '.', ''),
                    'served_at'    => $updatedAt,
                    // Nexus Corp kitchen staff: ids 2 & 3; Orion Labs: id 13
                    'recorder'     => in_array($userId, [14, 15]) ? 13 : (($orderId % 2 === 0) ? 2 : 3),
                ];
            }

            $orderId++;
        }

        DB::table('orders')->insert($orderRows);
        DB::table('order_items')->insert($orderItemRows);
    }

    // =========================================================================
    // TRANSACTIONS
    // Only for served orders. tendered_amount is rounded up to a realistic
    // cash amount. change_returned = tendered - total.
    // =========================================================================
    private function seedTransactions(): void
    {
        $transactionRows = [];
        $txId = 1;

        foreach ($this->servedOrderIds as $entry) {
            $total    = $entry['total_amount'];
            $tendered = $this->roundUpToCash($total);
            $change   = round($tendered - $total, 2);

            $transactionRows[] = [
                'id'              => $txId++,
                'order_id'        => $entry['order_id'],
                'recorded_by'     => $entry['recorder'],
                'tendered_amount' => number_format($tendered, 2, '.', ''),
                'change_returned' => number_format($change, 2, '.', ''),
                'created_at'      => $entry['served_at'],
                'updated_at'      => $entry['served_at'],
            ];
        }

        DB::table('transactions')->insert($transactionRows);
    }

    /**
     * Round a cash total up to the nearest realistic denomination:
     * 50 → 50 | 110 → 120 | 155 → 200 | 285 → 300
     */
    private function roundUpToCash(float $amount): float
    {
        $denominations = [10, 20, 50, 100, 200, 500];
        foreach ($denominations as $d) {
            $rounded = ceil($amount / $d) * $d;
            if ($rounded - $amount <= 50) {
                return $rounded;
            }
        }
        return ceil($amount / 100) * 100;
    }

    // =========================================================================
    // MESSAGES
    // Realistic internal comms — staff ↔ admin, various tags and priorities
    // All within Nexus Corp (tenant 1). Orion Labs gets 1 message.
    // User IDs reminder: 1=admin(Sarah), 2=Karim(kitchen), 3=Priya(kitchen)
    // =========================================================================
    private function seedMessages(): void
    {
        DB::table('messages')->insert([

            // ── item_requirement — stock alerts ───────────────────────────────
            [
                'id'          => 1,
                'tenant_id'   => 1,
                'sender_id'   => 2,   // Karim (kitchen staff)
                'receiver_id' => 1,   // Sarah (admin)
                'title'       => 'Beef Kala Bhuna running low',
                'content'     => 'Hi Sarah, just a heads-up — Beef Kala Bhuna is down to 8 portions. Already flagged it for restock but wanted to let you know directly. We\'ve been getting a lot of orders for it this week.',
                'tag'         => 'item_requirement',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subHours(2),
                'updated_at'  => $this->now->copy()->subHours(1),
            ],
            [
                'id'          => 2,
                'tenant_id'   => 1,
                'sender_id'   => 1,   // Sarah (admin)
                'receiver_id' => 2,   // Karim
                'title'       => 'RE: Beef Kala Bhuna running low',
                'content'     => 'Got it, Karim. I\'ve approved the restock of 30 portions. The supplier confirmed delivery for tomorrow morning. Keep an eye on the Piyaju and Khichuri levels too — both flagged this morning.',
                'tag'         => 'item_requirement',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subHours(1),
                'updated_at'  => $this->now->copy()->subHours(1),
            ],
            [
                'id'          => 3,
                'tenant_id'   => 1,
                'sender_id'   => 3,   // Priya (kitchen staff)
                'receiver_id' => 1,   // Sarah (admin)
                'title'       => 'Khichuri stock depleted',
                'content'     => 'Khichuri hit zero portions about an hour ago. I\'ve disabled it on the menu and submitted a restock request for 20 portions. Should we consider temporarily replacing it with the Vegetable Curry Set as the budget option?',
                'tag'         => 'item_requirement',
                'priority'    => 'high',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subHours(3),
                'updated_at'  => $this->now->copy()->subHours(3),
            ],

            // ── incident — equipment and operational issues ────────────────────
            [
                'id'          => 4,
                'tenant_id'   => 1,
                'sender_id'   => 2,   // Karim
                'receiver_id' => 1,   // Sarah
                'title'       => 'Refrigerator unit 2 temperature alarm',
                'content'     => 'The second refrigerator unit triggered a temperature alarm this morning around 9:15 AM. Temp reading was 8°C instead of the usual 3°C. I moved the dairy items (Mishti Doi, Lassi) to unit 1. Engineering have been notified and are coming in at 2 PM. Beverages are fine for now.',
                'tag'         => 'incident',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(9)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(0),
            ],
            [
                'id'          => 5,
                'tenant_id'   => 1,
                'sender_id'   => 1,   // Sarah
                'receiver_id' => 2,   // Karim
                'title'       => 'RE: Refrigerator unit 2 temperature alarm',
                'content'     => 'Good call moving the dairy. Document everything with timestamps for the incident log. If engineering can\'t fix it today, let me know and I\'ll arrange a rental unit for tomorrow. Do not serve any items that were in unit 2 for more than 2 hours above threshold.',
                'tag'         => 'incident',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(5),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(5),
            ],

            // ── customer_inquiry ───────────────────────────────────────────────
            [
                'id'          => 6,
                'tenant_id'   => 1,
                'sender_id'   => 2,   // Karim
                'receiver_id' => 1,   // Sarah
                'title'       => 'Complaint — Order #9 wrong item served',
                'content'     => 'One of the engineers (I think from the 4th floor) came back saying he received Vegetable Curry instead of Chicken Biryani. I checked order #9 and it was indeed Biryani. Not sure how the mix-up happened — we were very busy around 12:30 PM. I refunded his biryani and gave him the correct plate. Logging it here for your record.',
                'tag'         => 'customer_inquiry',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(4)->setHour(13)->setMinute(15),
                'updated_at'  => $this->now->copy()->subDays(4)->setHour(13)->setMinute(20),
            ],
            [
                'id'          => 7,
                'tenant_id'   => 1,
                'sender_id'   => 3,   // Priya
                'receiver_id' => 1,   // Sarah
                'title'       => 'Feedback — users asking about vegetarian protein option',
                'content'     => 'Several users have asked this week whether we\'ll be adding a paneer dish or any other vegetarian protein option. The Vegetable Curry Set is popular but people want something more filling. Thought I\'d pass it along — might be worth considering for next month\'s menu review.',
                'tag'         => 'customer_inquiry',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(3)->setHour(14)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(3)->setHour(14)->setMinute(0),
            ],

            // ── staff_duty — task assignments and handovers ────────────────────
            [
                'id'          => 8,
                'tenant_id'   => 1,
                'sender_id'   => 1,   // Sarah (admin assigning)
                'receiver_id' => 3,   // Priya (new hire, first solo shift)
                'title'       => 'Your first solo shift — Monday overview',
                'content'     => 'Hi Priya! Your first unsupported shift is this Monday. Karim will be on leave. Key things to remember: (1) Check all stock levels at 8:30 AM and flag anything below threshold immediately. (2) Orders start at 11:30 AM — keep the queue moving. (3) Mark any order as "ready" before calling the user. (4) For transactions, always confirm the tendered amount aloud before recording. I\'ll be reachable on my mobile if anything comes up.',
                'tag'         => 'staff_duty',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(5)->setHour(16)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(5)->setHour(17)->setMinute(0),
            ],
            [
                'id'          => 9,
                'tenant_id'   => 1,
                'sender_id'   => 2,   // Karim (handover to Priya)
                'receiver_id' => 3,   // Priya
                'title'       => 'Shift handover notes — Thursday',
                'content'     => 'Priya, leaving these notes before I head out: (1) Samosas batch for tomorrow is in the prep fridge — don\'t fry them until 11 AM. (2) Cold Coffee is disabled on the menu today — we ran out of the blending mix. Re-enable when the new stock arrives (should be Friday delivery). (3) 3 orders still in "preparing" status — I\'ve started them all, just needs finishing. Should be done by 1 PM. Good luck!',
                'tag'         => 'staff_duty',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(12)->setMinute(45),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(13)->setMinute(0),
            ],
            [
                'id'          => 10,
                'tenant_id'   => 1,
                'sender_id'   => 1,   // Sarah
                'receiver_id' => 2,   // Karim
                'title'       => 'Monthly cleaning schedule — this Saturday',
                'content'     => 'Reminder that the monthly deep clean is scheduled for this Saturday 8 AM–12 PM. The canteen will be closed to users. Please coordinate with Priya on which areas to cover. Cleaning checklist has been updated and is on the staff noticeboard.',
                'tag'         => 'staff_duty',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(2)->setHour(9)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(2)->setHour(9)->setMinute(0),
            ],

            // ── other ──────────────────────────────────────────────────────────
            [
                'id'          => 11,
                'tenant_id'   => 1,
                'sender_id'   => 2,   // Karim
                'receiver_id' => 1,   // Sarah
                'title'       => 'New payment denomination issue',
                'content'     => 'We had a few users trying to pay with ৳1000 notes today. We ran out of ৳100 change after 1 PM. Could we request a change float top-up? Ideally a batch of ৳50 and ৳100 notes before the start of each week.',
                'tag'         => 'other',
                'priority'    => 'low',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(6)->setHour(14)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(6)->setHour(14)->setMinute(0),
            ],
            [
                'id'          => 12,
                'tenant_id'   => 1,
                'sender_id'   => 3,   // Priya
                'receiver_id' => 1,   // Sarah
                'title'       => 'Suggestion — QR code menu display',
                'content'     => 'Hi Sarah, I was thinking — it might be helpful to have a QR code at the counter that links directly to the menu. Some users still walk up to ask what\'s available even though it\'s on the system. Would this be something Betopia could add as a feature?',
                'tag'         => 'other',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(2)->setHour(15)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(2)->setHour(15)->setMinute(30),
            ],

            // ── Orion Labs — one final message before lapse ───────────────────
            [
                'id'          => 13,
                'tenant_id'   => 2,
                'sender_id'   => 13,  // Lena (kitchen staff)
                'receiver_id' => 12,  // David (admin)
                'title'       => 'Canteen operations on hold?',
                'content'     => 'Hi David, I\'ve been told the subscription may not be renewed this cycle. Should I pause restocking orders and wind down canteen operations? Just need clarity before I place the next supplier order.',
                'tag'         => 'other',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(5),
                'updated_at'  => $this->now->copy()->subDays(5),
            ],
        ]);
    }

    // ── Holds served order metadata between seedOrders() and seedTransactions()
    private array $servedOrderIds = [];
}