<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * KitchenFlow — Master Database Seeder
 * Simulates ~2 weeks of realistic catering operations across 2 tenants:
 *   - Nexus Corp  → active subscription, full team, busy canteen
 *   - Orion Labs  → lapsed/inactive subscription, minimal data
 *
 * Uses insertGetId() throughout — no hardcoded IDs, sequences stay in sync.
 * Run: docker exec -it kitchenflow_backend php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    private Carbon $now;
    private Carbon $twoWeeksAgo;

    // Runtime ID references — populated during seeding, used for FK relationships
    private int $nexusId;
    private int $orionId;

    private int $sarahId;
    private int $karimId;
    private int $priyaId;
    private int $tanvirId;
    private int $nusratId;
    private int $arifId;
    private int $mehrinId;
    private int $rafiqId;
    private int $shabnamId;
    private int $zahidId;
    private int $farihaId;

    private int $davidId;
    private int $lenaId;
    private int $rezaId;
    private int $yukiId;

    private int $catRiceCurryId;
    private int $catSnacksId;
    private int $catBeveragesId;
    private int $catDessertsId;
    private int $catSeasonalId;
    private int $catOrionMainsId;
    private int $catOrionDrinksId;

    private int $itemBiryaniId;
    private int $itemKalaBhunaId;
    private int $itemDalBhatId;
    private int $itemKhichuriId;
    private int $itemVegCurryId;
    private int $itemSamosaId;
    private int $itemSpringRollId;
    private int $itemPiyajuId;
    private int $itemLemonTeaId;
    private int $itemMangoLassiId;
    private int $itemColdCoffeeId;
    private int $itemMishtiDoiId;
    private int $itemRasgollaId;
    private int $itemMangoPuddingId;
    private int $itemOrionPastaId;
    private int $itemOrionChickenId;
    private int $itemOrionWaterId;

    // Holds served order data for transaction seeding
    private array $servedOrders = [];

    public function run(): void
    {
        $this->now         = Carbon::now();
        $this->twoWeeksAgo = Carbon::now()->subDays(14);

        $this->call(RoleSeeder::class);
        $this->seedTenants();
        $this->seedUsers();
        $this->seedPasswordResetTokens();
        $this->seedCategories();
        $this->seedMenuItems();
        $this->call(StockSeeder::class);
        $this->seedOrders();
        $this->seedTransactions();
        $this->seedMessages();
    }

    // =========================================================================
    // TENANTS
    // =========================================================================
    private function seedTenants(): void
    {
        $this->nexusId = DB::table('tenants')->insertGetId([
            'name'                 => 'Nexus Corp',
            'subscription_active'  => true,
            'subscription_ends_at' => $this->now->copy()->addMonths(5),
            'created_at'           => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'           => $this->twoWeeksAgo->copy()->subDays(30),
        ]);

        $this->orionId = DB::table('tenants')->insertGetId([
            'name'                 => 'Orion Labs',
            'subscription_active'  => false,
            'subscription_ends_at' => $this->now->copy()->subDays(3),
            'created_at'           => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at'           => $this->now->copy()->subDays(3),
        ]);
    }

    // =========================================================================
    // USERS
    // =========================================================================
    private function seedUsers(): void
    {
        $pass = Hash::make('password');
        $n    = $this->nexusId;
        $o    = $this->orionId;

        // ── Nexus Corp ────────────────────────────────────────────────────────
        $this->sarahId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Sarah Ahmed',
            'email'      => 'sarah.ahmed@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'admin',
            'role_id'    => 1,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->karimId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Karim Hossain',
            'email'      => 'karim.hossain@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'kitchen_staff',
            'role_id'    => 2,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->priyaId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Priya Nair',
            'email'      => 'priya.nair@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'kitchen_staff',
            'role_id'    => 2,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(5),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(5),
            'deleted_at' => null,
        ]);

        $this->tanvirId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Tanvir Mahmud',
            'email'      => 'tanvir.mahmud@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(28),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(28),
            'deleted_at' => null,
        ]);

        $this->nusratId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Nusrat Jahan',
            'email'      => 'nusrat.jahan@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(28),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(28),
            'deleted_at' => null,
        ]);

        $this->arifId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Arif Chowdhury',
            'email'      => 'arif.chowdhury@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(20),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(20),
            'deleted_at' => null,
        ]);

        $this->mehrinId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Mehrin Sultana',
            'email'      => 'mehrin.sultana@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(20),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(20),
            'deleted_at' => null,
        ]);

        $this->rafiqId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Rafiq Islam',
            'email'      => 'rafiq.islam@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => false, // suspended
            'created_at' => $this->twoWeeksAgo->copy()->subDays(25),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(2),
            'deleted_at' => null,
        ]);

        $this->shabnamId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Shabnam Akter',
            'email'      => 'shabnam.akter@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(15),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(15),
            'deleted_at' => null,
        ]);

        $this->zahidId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Zahid Hassan',
            'email'      => 'zahid.hassan@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => false,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(8),
            'deleted_at' => $this->twoWeeksAgo->copy()->subDays(8), // soft-deleted — left company
        ]);

        $this->farihaId = DB::table('users')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Fariha Begum',
            'email'      => 'fariha.begum@nexuscorp.com',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(10),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(10),
            'deleted_at' => null,
        ]);

        // ── Orion Labs ────────────────────────────────────────────────────────
        $this->davidId = DB::table('users')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'David Park',
            'email'      => 'david.park@orionlabs.io',
            'password'   => $pass,
            'role'       => 'admin',
            'role_id'    => 1,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at' => null,
        ]);

        $this->lenaId = DB::table('users')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'Lena Müller',
            'email'      => 'lena.muller@orionlabs.io',
            'password'   => $pass,
            'role'       => 'kitchen_staff',
            'role_id'    => 2,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at' => null,
        ]);

        $this->rezaId = DB::table('users')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'Reza Moradi',
            'email'      => 'reza.moradi@orionlabs.io',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at' => null,
        ]);

        $this->yukiId = DB::table('users')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'Yuki Tanaka',
            'email'      => 'yuki.tanaka@orionlabs.io',
            'password'   => $pass,
            'role'       => 'user',
            'role_id'    => 3,
            'is_active'  => true,
            'created_at' => $this->twoWeeksAgo->copy()->subDays(55),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(55),
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // PASSWORD RESET TOKENS
    // =========================================================================
    private function seedPasswordResetTokens(): void
    {
        DB::table('password_reset_tokens')->insert([
            [
                'user_id'    => $this->nusratId,
                'token'      => bin2hex(random_bytes(32)),
                'expires_at' => $this->now->copy()->addMinutes(45),
                'created_at' => $this->now->copy()->subMinutes(15),
            ],
            [
                'user_id'    => $this->arifId,
                'token'      => bin2hex(random_bytes(32)),
                'expires_at' => $this->now->copy()->subHours(2), // expired — never used
                'created_at' => $this->now->copy()->subHours(3),
            ],
        ]);
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================
    private function seedCategories(): void
    {
        $n = $this->nexusId;
        $o = $this->orionId;

        $this->catRiceCurryId = DB::table('categories')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Rice & Curry',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->catSnacksId = DB::table('categories')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Snacks & Starters',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->catBeveragesId = DB::table('categories')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Beverages',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->catDessertsId = DB::table('categories')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Desserts',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at' => null,
        ]);

        $this->catSeasonalId = DB::table('categories')->insertGetId([
            'tenant_id'  => $n,
            'name'       => 'Seasonal Specials',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(12),
            'deleted_at' => $this->twoWeeksAgo->copy()->subDays(12), // soft-deleted
        ]);

        $this->catOrionMainsId = DB::table('categories')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'Mains',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at' => null,
        ]);

        $this->catOrionDrinksId = DB::table('categories')->insertGetId([
            'tenant_id'  => $o,
            'name'       => 'Drinks',
            'created_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at' => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // MENU ITEMS
    //
    // IMAGE FILENAMES — save to storage/app/public/menu/:
    //   nexus_chicken_biryani.jpeg      nexus_beef_kala_bhuna.jpeg
    //   nexus_dal_bhat.jpeg             nexus_khichuri.jpeg
    //   nexus_vegetable_curry.jpeg      nexus_samosa.jpeg
    //   nexus_spring_roll.jpeg          nexus_piyaju.jpeg
    //   nexus_lemon_tea.jpeg            nexus_mango_lassi.jpeg
    //   nexus_cold_coffee.jpeg          nexus_mishti_doi.jpeg
    //   nexus_rasgolla.jpeg             nexus_mango_pudding.jpeg
    //   orion_pasta_bolognese.jpeg      orion_grilled_chicken.jpeg
    //   orion_mineral_water.jpeg
    // =========================================================================
    private function seedMenuItems(): void
    {
        // ── Rice & Curry ──────────────────────────────────────────────────────
        $this->itemBiryaniId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catRiceCurryId,
            'name'                      => 'Chicken Biryani',
            'description'               => 'Aromatic basmati rice slow-cooked with tender chicken, saffron, and whole spices. Served with raita and sliced lemon.',
            'image_path'                => '/menu/nexus_chicken_biryani.jpeg',
            'price'                     => '120.00',
            'stock_quantity'            => 35,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->now->copy()->subHours(2),
            'deleted_at'                => null,
        ]);

        $this->itemKalaBhunaId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catRiceCurryId,
            'name'                      => 'Beef Kala Bhuna',
            'description'               => 'Slow-roasted Chittagonian-style beef cooked in a deep, dark gravy of caramelised onions, whole spices, and mustard oil. Best paired with white rice.',
            'image_path'                => '/menu/nexus_beef_kala_bhuna.jpeg',
            'price'                     => '150.00',
            'stock_quantity'            => 8,
            'low_stock_threshold'       => 10,
            'needs_restock'             => true,
            'requested_restock_quantity' => 30,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->now->copy()->subHours(1),
            'deleted_at'                => null,
        ]);

        $this->itemDalBhatId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catRiceCurryId,
            'name'                      => 'Dal Bhat',
            'description'               => 'Classic Bengali comfort meal — steamed white rice served with a generous portion of turmeric-lentil dal, fried hilsa or aloo bhaji on the side.',
            'image_path'                => '/menu/nexus_dal_bhat.jpeg',
            'price'                     => '70.00',
            'stock_quantity'            => 50,
            'low_stock_threshold'       => 15,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at'                => null,
        ]);

        $this->itemKhichuriId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catRiceCurryId,
            'name'                      => 'Khichuri',
            'description'               => 'Warm rice and lentil porridge seasoned with turmeric, cumin, and bay leaves, topped with ghee. A popular rainy-day special served with fried eggplant.',
            'image_path'                => '/menu/nexus_khichuri.jpeg',
            'price'                     => '80.00',
            'stock_quantity'            => 0,
            'low_stock_threshold'       => 10,
            'needs_restock'             => true,
            'requested_restock_quantity' => 20,
            'is_available'              => false, // auto-disabled at zero stock
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->now->copy()->subHours(3),
            'deleted_at'                => null,
        ]);

        $this->itemVegCurryId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catRiceCurryId,
            'name'                      => 'Vegetable Curry Set',
            'description'               => 'Seasonal vegetables simmered in a light tomato-based curry, served with two pieces of soft paratha. Suitable for vegetarians.',
            'image_path'                => '/menu/nexus_vegetable_curry.jpeg',
            'price'                     => '65.00',
            'stock_quantity'            => 22,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'deleted_at'                => null,
        ]);

        // ── Snacks & Starters ─────────────────────────────────────────────────
        $this->itemSamosaId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catSnacksId,
            'name'                      => 'Vegetable Samosa (2 pcs)',
            'description'               => 'Crispy golden pastry shells filled with spiced potatoes, peas, and herbs. Served with mint-coriander chutney.',
            'image_path'                => '/menu/nexus_samosa.jpeg',
            'price'                     => '30.00',
            'stock_quantity'            => 60,
            'low_stock_threshold'       => 20,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at'                => null,
        ]);

        $this->itemSpringRollId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catSnacksId,
            'name'                      => 'Chicken Spring Roll (2 pcs)',
            'description'               => 'Crunchy rolls filled with shredded chicken, cabbage, carrots, and sesame-soy sauce. Served hot with sweet chili dip.',
            'image_path'                => '/menu/nexus_spring_roll.jpeg',
            'price'                     => '45.00',
            'stock_quantity'            => 40,
            'low_stock_threshold'       => 15,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(25),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(25),
            'deleted_at'                => null,
        ]);

        $this->itemPiyajuId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catSnacksId,
            'name'                      => 'Piyaju (4 pcs)',
            'description'               => 'Traditional lentil fritters mixed with onion, green chilli, and fresh coriander, fried to a light crisp. A Dhaka canteen staple.',
            'image_path'                => '/menu/nexus_piyaju.jpeg',
            'price'                     => '20.00',
            'stock_quantity'            => 5,
            'low_stock_threshold'       => 15,
            'needs_restock'             => true,
            'requested_restock_quantity' => 40,
            'is_available'              => true, // low but still available
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->now->copy()->subMinutes(30),
            'deleted_at'                => null,
        ]);

        // ── Beverages ─────────────────────────────────────────────────────────
        $this->itemLemonTeaId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catBeveragesId,
            'name'                      => 'Lemon Tea',
            'description'               => 'Freshly brewed black tea with a squeeze of lemon, lightly sweetened. Served hot in a glass.',
            'image_path'                => '/menu/nexus_lemon_tea.jpeg',
            'price'                     => '15.00',
            'stock_quantity'            => 100,
            'low_stock_threshold'       => 25,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'deleted_at'                => null,
        ]);

        $this->itemMangoLassiId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catBeveragesId,
            'name'                      => 'Mango Lassi',
            'description'               => 'Thick and chilled yoghurt drink blended with ripe Alphonso mango pulp, a pinch of cardamom, and crushed ice.',
            'image_path'                => '/menu/nexus_mango_lassi.jpeg',
            'price'                     => '40.00',
            'stock_quantity'            => 28,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(20),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(20),
            'deleted_at'                => null,
        ]);

        $this->itemColdCoffeeId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catBeveragesId,
            'name'                      => 'Cold Coffee',
            'description'               => 'Blended iced coffee with full-cream milk, sugar, and a dash of vanilla essence. A canteen favourite on warm afternoons.',
            'image_path'                => '/menu/nexus_cold_coffee.jpeg',
            'price'                     => '50.00',
            'stock_quantity'            => 18,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => false, // manually disabled by kitchen staff
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(18),
            'updated_at'                => $this->now->copy()->subHours(1),
            'deleted_at'                => null,
        ]);

        // ── Desserts ──────────────────────────────────────────────────────────
        $this->itemMishtiDoiId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catDessertsId,
            'name'                      => 'Mishti Doi',
            'description'               => 'Traditional Bengali sweet yoghurt, gently fermented and set in earthen pots. Mildly sweet with a slightly caramelised top.',
            'image_path'                => '/menu/nexus_mishti_doi.jpeg',
            'price'                     => '35.00',
            'stock_quantity'            => 30,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'deleted_at'                => null,
        ]);

        $this->itemRasgollaId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catDessertsId,
            'name'                      => 'Rasgolla (2 pcs)',
            'description'               => 'Soft, spongy cottage cheese balls soaked in light sugar syrup. A classic Bengali sweet that melts in the mouth.',
            'image_path'                => '/menu/nexus_rasgolla.jpeg',
            'price'                     => '25.00',
            'stock_quantity'            => 45,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(28),
            'deleted_at'                => null,
        ]);

        // ── Seasonal Specials (soft-deleted category — item archived too) ──────
        $this->itemMangoPuddingId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catSeasonalId,
            'name'                      => 'Mango Pudding',
            'description'               => 'Chilled pudding made from fresh seasonal mango pulp, condensed milk, and agar. Available during mango season only.',
            'image_path'                => '/menu/nexus_mango_pudding.jpeg',
            'price'                     => '55.00',
            'stock_quantity'            => 0,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => false,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(30),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(12),
            'deleted_at'                => $this->twoWeeksAgo->copy()->subDays(12),
        ]);

        // ── Orion Labs ────────────────────────────────────────────────────────
        $this->itemOrionPastaId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catOrionMainsId,
            'name'                      => 'Pasta Bolognese',
            'description'               => 'Al dente penne tossed in a rich beef and tomato ragù, slow-cooked with garlic, red wine, and fresh basil. Finished with parmesan.',
            'image_path'                => '/menu/orion_pasta_bolognese.jpeg',
            'price'                     => '180.00',
            'stock_quantity'            => 12,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at'                => null,
        ]);

        $this->itemOrionChickenId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catOrionMainsId,
            'name'                      => 'Grilled Chicken Plate',
            'description'               => 'Marinated chicken breast grilled to order, served with roasted potatoes and garden salad.',
            'image_path'                => '/menu/orion_grilled_chicken.jpeg',
            'price'                     => '200.00',
            'stock_quantity'            => 9,
            'low_stock_threshold'       => 10,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at'                => null,
        ]);

        $this->itemOrionWaterId = DB::table('menu_items')->insertGetId([
            'category_id'               => $this->catOrionDrinksId,
            'name'                      => 'Mineral Water (500ml)',
            'description'               => 'Chilled bottled mineral water.',
            'image_path'                => '/menu/orion_mineral_water.jpeg',
            'price'                     => '20.00',
            'stock_quantity'            => 80,
            'low_stock_threshold'       => 20,
            'needs_restock'             => false,
            'requested_restock_quantity' => null,
            'is_available'              => true,
            'created_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'updated_at'                => $this->twoWeeksAgo->copy()->subDays(60),
            'deleted_at'                => null,
        ]);
    }

    // =========================================================================
    // ORDERS + ORDER ITEMS
    // =========================================================================
    private function seedOrders(): void
    {
        // Shorthand aliases for readability
        $tanvir  = $this->tanvirId;
        $nusrat  = $this->nusratId;
        $arif    = $this->arifId;
        $mehrin  = $this->mehrinId;
        $shabnam = $this->shabnamId;
        $zahid   = $this->zahidId;
        $fariha  = $this->farihaId;
        $reza    = $this->rezaId;
        $yuki    = $this->yukiId;

        $bir   = $this->itemBiryaniId;
        $kal   = $this->itemKalaBhunaId;
        $dal   = $this->itemDalBhatId;
        $veg   = $this->itemVegCurryId;
        $sam   = $this->itemSamosaId;
        $spr   = $this->itemSpringRollId;
        $piy   = $this->itemPiyajuId;
        $tea   = $this->itemLemonTeaId;
        $las   = $this->itemMangoLassiId;
        $doi   = $this->itemMishtiDoiId;
        $ras   = $this->itemRasgollaId;
        $pasta   = $this->itemOrionPastaId;
        $chicken = $this->itemOrionChickenId;
        $water   = $this->itemOrionWaterId;

        // Format: [user_id, status, notes, days_ago, [[menu_item_id, qty, unit_price], ...]]
        // unit_price is snapshotted — reflects price at time of order placement
        $blueprints = [
            // ── Day 14 ────────────────────────────────────────────────────────
            [$tanvir,  'served',    null,                        14, [[$bir,1,'120.00'],[$tea,1,'15.00']]],
            [$nusrat,  'served',    null,                        14, [[$dal,1,'70.00'],[$sam,2,'30.00']]],
            [$arif,    'canceled',  null,                        14, [[$kal,1,'150.00']]],

            // ── Day 13 ────────────────────────────────────────────────────────
            [$tanvir,  'served',    null,                        13, [[$bir,1,'120.00'],[$doi,1,'35.00']]],
            [$mehrin,  'served',    null,                        13, [[$veg,1,'65.00'],[$tea,2,'15.00']]],
            [$shabnam, 'served',    'Extra spicy please',        13, [[$kal,1,'150.00'],[$sam,1,'30.00']]],

            // ── Day 12 ────────────────────────────────────────────────────────
            [$nusrat,  'served',    null,                        12, [[$dal,2,'70.00'],[$ras,1,'25.00']]],
            [$fariha,  'served',    null,                        12, [[$bir,1,'120.00'],[$las,1,'40.00']]],

            // ── Day 11 ────────────────────────────────────────────────────────
            [$tanvir,  'served',    null,                        11, [[$spr,2,'45.00'],[$tea,1,'15.00']]],
            [$arif,    'served',    null,                        11, [[$bir,1,'120.00'],[$doi,1,'35.00']]],
            [$mehrin,  'canceled',  null,                        11, [[$veg,1,'65.00'],[$piy,2,'20.00']]],

            // ── Day 10 ────────────────────────────────────────────────────────
            [$shabnam, 'served',    null,                        10, [[$dal,1,'70.00'],[$sam,2,'30.00'],[$tea,1,'15.00']]],
            [$nusrat,  'served',    null,                        10, [[$kal,1,'150.00'],[$ras,2,'25.00']]],

            // ── Day 9 ─────────────────────────────────────────────────────────
            [$tanvir,  'served',    'No onion in curry',          9, [[$bir,1,'120.00'],[$tea,1,'15.00']]],
            [$fariha,  'served',    null,                          9, [[$veg,1,'65.00'],[$sam,1,'30.00'],[$doi,1,'35.00']]],
            [$arif,    'served',    null,                          9, [[$spr,1,'45.00'],[$las,1,'40.00']]],

            // ── Day 8 (Zahid's last order — soft-deleted same day) ────────────
            [$zahid,   'served',    null,                          8, [[$dal,1,'70.00'],[$piy,2,'20.00']]],
            [$mehrin,  'served',    null,                          8, [[$bir,1,'120.00'],[$tea,1,'15.00']]],

            // ── Day 7 ─────────────────────────────────────────────────────────
            [$tanvir,  'served',    null,                          7, [[$kal,1,'150.00'],[$ras,1,'25.00']]],
            [$nusrat,  'served',    null,                          7, [[$bir,1,'120.00'],[$las,1,'40.00'],[$tea,1,'15.00']]],
            [$shabnam, 'canceled',  null,                          7, [[$spr,2,'45.00']]],

            // ── Day 6 ─────────────────────────────────────────────────────────
            [$fariha,  'served',    null,                          6, [[$dal,1,'70.00'],[$sam,2,'30.00']]],
            [$arif,    'served',    'Less salt please',            6, [[$bir,1,'120.00'],[$doi,1,'35.00']]],

            // ── Day 5 ─────────────────────────────────────────────────────────
            [$tanvir,  'served',    null,                          5, [[$veg,1,'65.00'],[$tea,2,'15.00'],[$ras,1,'25.00']]],
            [$mehrin,  'served',    null,                          5, [[$kal,1,'150.00'],[$las,1,'40.00']]],

            // ── Day 4 ─────────────────────────────────────────────────────────
            [$nusrat,  'served',    null,                          4, [[$bir,1,'120.00'],[$sam,2,'30.00']]],
            [$shabnam, 'served',    null,                          4, [[$dal,1,'70.00'],[$tea,1,'15.00'],[$doi,1,'35.00']]],
            [$fariha,  'served',    null,                          4, [[$spr,2,'45.00'],[$ras,1,'25.00']]],

            // ── Day 3 ─────────────────────────────────────────────────────────
            [$tanvir,  'served',    'Extra rice',                  3, [[$bir,2,'120.00'],[$tea,1,'15.00']]],
            [$arif,    'served',    null,                          3, [[$kal,1,'150.00'],[$las,1,'40.00']]],

            // ── Day 2 ─────────────────────────────────────────────────────────
            [$nusrat,  'served',    null,                          2, [[$veg,1,'65.00'],[$sam,1,'30.00'],[$tea,1,'15.00']]],
            [$mehrin,  'served',    null,                          2, [[$bir,1,'120.00'],[$doi,1,'35.00'],[$ras,1,'25.00']]],

            // ── Today — active queue ───────────────────────────────────────────
            [$tanvir,  'served',    null,                          0, [[$dal,1,'70.00'],[$tea,1,'15.00']]],
            [$shabnam, 'ready',     null,                          0, [[$bir,1,'120.00'],[$las,1,'40.00']]],
            [$fariha,  'preparing', 'Add extra chutney on side',   0, [[$sam,3,'30.00'],[$spr,1,'45.00']]],
            [$nusrat,  'preparing', null,                          0, [[$kal,1,'150.00'],[$ras,2,'25.00']]],
            [$arif,    'pending',   null,                          0, [[$veg,1,'65.00'],[$piy,2,'20.00']]],
            [$mehrin,  'pending',   'Mild spice only',             0, [[$bir,1,'120.00'],[$tea,2,'15.00']]],

            // ── Orion Labs — historical orders before subscription lapsed ──────
            [$reza,    'served',    null,                         20, [[$pasta,1,'180.00'],[$water,1,'20.00']]],
            [$yuki,    'served',    null,                         18, [[$chicken,1,'200.00']]],
            [$reza,    'served',    null,                         15, [[$pasta,1,'180.00']]],
        ];

        foreach ($blueprints as $i => $bp) {
            [$userId, $status, $notes, $daysAgo, $items] = $bp;

            $total = array_reduce($items, fn($carry, $item) => $carry + ($item[1] * (float) $item[2]), 0.0);

            $createdAt = $daysAgo === 0
                ? $this->now->copy()->subMinutes($i * 8 + 5)
                : $this->now->copy()->subDays($daysAgo)->setHour(rand(11, 13))->setMinute(rand(0, 59));

            $updatedAt = $status === 'served'
                ? $createdAt->copy()->addMinutes(rand(10, 25))
                : $createdAt->copy();

            $orderId = DB::table('orders')->insertGetId([
                'user_id'      => $userId,
                'status'       => $status,
                'total_amount' => number_format($total, 2, '.', ''),
                'notes'        => $notes,
                'created_at'   => $createdAt,
                'updated_at'   => $updatedAt,
            ]);

            foreach ($items as $item) {
                [$menuItemId, $qty, $unitPrice] = $item;
                DB::table('order_items')->insertGetId([
                    'order_id'     => $orderId,
                    'menu_item_id' => $menuItemId,
                    'quantity'     => $qty,
                    'unit_price'   => $unitPrice,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
            }

            if ($status === 'served') {
                $isOrion  = in_array($userId, [$this->rezaId, $this->yukiId]);
                $recorder = $isOrion
                    ? $this->lenaId
                    : (($orderId % 2 === 0) ? $this->karimId : $this->priyaId);

                $this->servedOrders[] = [
                    'order_id'     => $orderId,
                    'total_amount' => $total,
                    'served_at'    => $updatedAt,
                    'recorded_by'  => $recorder,
                ];
            }
        }
    }

    // =========================================================================
    // TRANSACTIONS — only for served orders
    // =========================================================================
    private function seedTransactions(): void
    {
        foreach ($this->servedOrders as $entry) {
            $tendered = $this->roundUpToCash($entry['total_amount']);
            $change   = round($tendered - $entry['total_amount'], 2);

            DB::table('transactions')->insertGetId([
                'order_id'        => $entry['order_id'],
                'recorded_by'     => $entry['recorded_by'],
                'tendered_amount' => number_format($tendered, 2, '.', ''),
                'change_returned' => number_format($change, 2, '.', ''),
                'created_at'      => $entry['served_at'],
                'updated_at'      => $entry['served_at'],
            ]);
        }
    }

    private function roundUpToCash(float $amount): float
    {
        foreach ([10, 20, 50, 100, 200, 500] as $d) {
            $rounded = ceil($amount / $d) * $d;
            if ($rounded - $amount <= 50) {
                return $rounded;
            }
        }
        return ceil($amount / 100) * 100;
    }

    // =========================================================================
    // MESSAGES
    // =========================================================================
    private function seedMessages(): void
    {
        $nexus = $this->nexusId;
        $orion = $this->orionId;
        $sarah = $this->sarahId;
        $karim = $this->karimId;
        $priya = $this->priyaId;
        $david = $this->davidId;
        $lena  = $this->lenaId;

        $messages = [
            // ── item_requirement ──────────────────────────────────────────────
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $karim,
                'receiver_id' => $sarah,
                'title'       => 'Beef Kala Bhuna running low',
                'content'     => 'Hi Sarah, just a heads-up — Beef Kala Bhuna is down to 8 portions. Already flagged it for restock but wanted to let you know directly. We\'ve been getting a lot of orders for it this week.',
                'tag'         => 'item_requirement',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subHours(2),
                'updated_at'  => $this->now->copy()->subHours(1),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $sarah,
                'receiver_id' => $karim,
                'title'       => 'RE: Beef Kala Bhuna running low',
                'content'     => 'Got it, Karim. I\'ve approved the restock of 30 portions. The supplier confirmed delivery for tomorrow morning. Keep an eye on the Piyaju and Khichuri levels too — both flagged this morning.',
                'tag'         => 'item_requirement',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subHours(1),
                'updated_at'  => $this->now->copy()->subHours(1),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $priya,
                'receiver_id' => $sarah,
                'title'       => 'Khichuri stock depleted',
                'content'     => 'Khichuri hit zero portions about an hour ago. I\'ve disabled it on the menu and submitted a restock request for 20 portions. Should we consider temporarily replacing it with the Vegetable Curry Set as the budget option?',
                'tag'         => 'item_requirement',
                'priority'    => 'high',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subHours(3),
                'updated_at'  => $this->now->copy()->subHours(3),
            ],

            // ── incident ──────────────────────────────────────────────────────
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $karim,
                'receiver_id' => $sarah,
                'title'       => 'Refrigerator unit 2 temperature alarm',
                'content'     => 'The second refrigerator unit triggered a temperature alarm this morning around 9:15 AM. Temp reading was 8°C instead of the usual 3°C. I moved the dairy items (Mishti Doi, Lassi) to unit 1. Engineering have been notified and are coming in at 2 PM. Beverages are fine for now.',
                'tag'         => 'incident',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(9)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(0),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $sarah,
                'receiver_id' => $karim,
                'title'       => 'RE: Refrigerator unit 2 temperature alarm',
                'content'     => 'Good call moving the dairy. Document everything with timestamps for the incident log. If engineering can\'t fix it today, let me know and I\'ll arrange a rental unit for tomorrow. Do not serve any items that were in unit 2 for more than 2 hours above threshold.',
                'tag'         => 'incident',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(5),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(10)->setMinute(5),
            ],

            // ── customer_inquiry ──────────────────────────────────────────────
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $karim,
                'receiver_id' => $sarah,
                'title'       => 'Complaint — wrong item served',
                'content'     => 'One of the engineers came back saying he received Vegetable Curry instead of Chicken Biryani. I checked the order and it was indeed Biryani. Not sure how the mix-up happened — we were very busy around 12:30 PM. I refunded his biryani and gave him the correct plate. Logging it here for your record.',
                'tag'         => 'customer_inquiry',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(4)->setHour(13)->setMinute(15),
                'updated_at'  => $this->now->copy()->subDays(4)->setHour(13)->setMinute(20),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $priya,
                'receiver_id' => $sarah,
                'title'       => 'Feedback — users asking about vegetarian protein option',
                'content'     => 'Several users have asked this week whether we\'ll be adding a paneer dish or any other vegetarian protein option. The Vegetable Curry Set is popular but people want something more filling. Thought I\'d pass it along — might be worth considering for next month\'s menu review.',
                'tag'         => 'customer_inquiry',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(3)->setHour(14)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(3)->setHour(14)->setMinute(0),
            ],

            // ── staff_duty ────────────────────────────────────────────────────
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $sarah,
                'receiver_id' => $priya,
                'title'       => 'Your first solo shift — Monday overview',
                'content'     => 'Hi Priya! Your first unsupported shift is this Monday. Karim will be on leave. Key things to remember: (1) Check all stock levels at 8:30 AM and flag anything below threshold immediately. (2) Orders start at 11:30 AM — keep the queue moving. (3) Mark any order as "ready" before calling the user. (4) For transactions, always confirm the tendered amount aloud before recording. I\'ll be reachable on my mobile if anything comes up.',
                'tag'         => 'staff_duty',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(5)->setHour(16)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(5)->setHour(17)->setMinute(0),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $karim,
                'receiver_id' => $priya,
                'title'       => 'Shift handover notes — Thursday',
                'content'     => 'Priya, leaving these notes before I head out: (1) Samosas batch for tomorrow is in the prep fridge — don\'t fry them until 11 AM. (2) Cold Coffee is disabled on the menu today — we ran out of the blending mix. Re-enable when the new stock arrives (should be Friday delivery). (3) 3 orders still in "preparing" status — I\'ve started them all, just needs finishing. Should be done by 1 PM. Good luck!',
                'tag'         => 'staff_duty',
                'priority'    => 'medium',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(1)->setHour(12)->setMinute(45),
                'updated_at'  => $this->now->copy()->subDays(1)->setHour(13)->setMinute(0),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $sarah,
                'receiver_id' => $karim,
                'title'       => 'Monthly cleaning schedule — this Saturday',
                'content'     => 'Reminder that the monthly deep clean is scheduled for this Saturday 8 AM–12 PM. The canteen will be closed to users. Please coordinate with Priya on which areas to cover. Cleaning checklist has been updated and is on the staff noticeboard.',
                'tag'         => 'staff_duty',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(2)->setHour(9)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(2)->setHour(9)->setMinute(0),
            ],

            // ── other ─────────────────────────────────────────────────────────
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $karim,
                'receiver_id' => $sarah,
                'title'       => 'Change float running low',
                'content'     => 'We had a few users trying to pay with ৳1000 notes today. We ran out of ৳100 change after 1 PM. Could we request a change float top-up? Ideally a batch of ৳50 and ৳100 notes before the start of each week.',
                'tag'         => 'other',
                'priority'    => 'low',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(6)->setHour(14)->setMinute(0),
                'updated_at'  => $this->now->copy()->subDays(6)->setHour(14)->setMinute(0),
            ],
            [
                'tenant_id'   => $nexus,
                'sender_id'   => $priya,
                'receiver_id' => $sarah,
                'title'       => 'Suggestion — QR code menu display',
                'content'     => 'Hi Sarah, I was thinking — it might be helpful to have a QR code at the counter that links directly to the menu. Some users still walk up to ask what\'s available even though it\'s on the system. Would this be something Betopia could add as a feature?',
                'tag'         => 'other',
                'priority'    => 'low',
                'is_read'     => false,
                'created_at'  => $this->now->copy()->subDays(2)->setHour(15)->setMinute(30),
                'updated_at'  => $this->now->copy()->subDays(2)->setHour(15)->setMinute(30),
            ],

            // ── Orion Labs — final message before lapse ───────────────────────
            [
                'tenant_id'   => $orion,
                'sender_id'   => $lena,
                'receiver_id' => $david,
                'title'       => 'Canteen operations on hold?',
                'content'     => 'Hi David, I\'ve been told the subscription may not be renewed this cycle. Should I pause restocking orders and wind down canteen operations? Just need clarity before I place the next supplier order.',
                'tag'         => 'other',
                'priority'    => 'high',
                'is_read'     => true,
                'created_at'  => $this->now->copy()->subDays(5),
                'updated_at'  => $this->now->copy()->subDays(5),
            ],
        ];

        foreach ($messages as $message) {
            DB::table('messages')->insertGetId($message);
        }
    }
}