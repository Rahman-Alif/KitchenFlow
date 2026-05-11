<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\MenuItemFactory;
use Database\Factories\MessageFactory;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents; // disables model observers — critical for stock/availability observer

    // ────────────────────────────────────────────────────────
    // SCALE CONSTANTS
    // ────────────────────────────────────────────────────────
    private const TENANTS             = 300;

    private const ORDERS_MIN_PER_USER = 15;
    private const ORDERS_MAX_PER_USER = 25;
    private const MESSAGES_PER_TENANT = 40;
    private const MOVEMENTS_PER_ITEM  = 30; // realistic time-series depth per stock item
    private const CHUNK               = 500;

    // ── Tenant size tiers ────────────────────────────────────
    // Controls order volume multiplier and user count scaling.
    private const TENANT_TIERS = [
        'small'  => ['weight' => 5, 'order_multiplier' => 0.5,  'price_premium' => 0.0, 'users_min' => 10, 'users_max' => 30],
        'medium' => ['weight' => 3, 'order_multiplier' => 1.0,  'price_premium' => 0.1, 'users_min' => 40, 'users_max' => 80],
        'large'  => ['weight' => 2, 'order_multiplier' => 1.8,  'price_premium' => 0.2, 'users_min' => 100,'users_max' => 200],
    ];

    // ── Popularity tier → weighted pick value ────────────────
    private const TIER_PICK_WEIGHTS = [
        MenuItemFactory::TIER_HOT     => 5,
        MenuItemFactory::TIER_REGULAR => 1,
        MenuItemFactory::TIER_SLOW    => 0, // sentinel — resolved to 0.3 below
    ];

    // ── Basket affinity companion injection probability ───────
    // When an anchor item is in an order, each of its companions
    // is added with this probability.
    private const AFFINITY_PROB = 0.72;

    // ── Day-of-week order volume weights (0=Sun … 6=Sat) ─────
    // This is a B2B org SaaS product (office/workplace canteen).
    // Fri & Sat are weekends in BD — most employees are off, so only
    // a skeleton crew places orders. Weight is very low but non-zero
    // to reflect that small number of weekend personnel.
    private const DOW_WEIGHTS = [
        0 => 0.15, // Sunday   (weekend — skeleton crew only)
        1 => 1.1,  // Monday
        2 => 1.2,  // Tuesday  (peak weekday)
        3 => 1.2,  // Wednesday
        4 => 1.1,  // Thursday
        5 => 0.10, // Friday   (weekend — very few orders)
        6 => 0.12, // Saturday (weekend — very few orders)
    ];

    // ── Hour-of-day order volume weights (0–23) ──────────────
    // Models lunch and dinner peaks; dead hours near zero.
    private const HOD_WEIGHTS = [
        0  => 0.0, 1  => 0.0, 2  => 0.0, 3  => 0.0,
        4  => 0.0, 5  => 0.0, 6  => 0.0, 7  => 0.3,
        8  => 0.6, 9  => 0.9, 10 => 1.2, 11 => 2.0,
        12 => 3.0, 13 => 3.5, 14 => 2.5, 15 => 1.0,
        16 => 0.7, 17 => 0.8, 18 => 1.5, 19 => 3.0,
        20 => 3.5, 21 => 2.5, 22 => 1.0, 23 => 0.3,
    ];

    // ── Growth ramp: month index (0=oldest) → multiplier ─────
    // Simulates a restaurant growing its order volume over 6 months.
    private const GROWTH_RAMP = [0 => 0.55, 1 => 0.70, 2 => 0.80, 3 => 0.90, 4 => 1.00, 5 => 1.15];

    private string $hashedPassword;

    // ────────────────────────────────────────────────────────
    // ENTRY POINT
    // ────────────────────────────────────────────────────────
    public function run(): void
    {
        $this->hashedPassword = Hash::make('password');

        $catalog        = MenuItemFactory::catalog();
        $affinityGroups = MenuItemFactory::affinityGroups();
        $categoryHours  = MenuItemFactory::categoryHours();
        $templates      = MessageFactory::templates();

        $this->command->info('Seeding roles...');
        $this->seedRoles();

        for ($i = 1; $i <= self::TENANTS; $i++) {
            $tier = $this->pickTenantTier();
            $this->command->info("Seeding tenant {$i} / " . self::TENANTS . " [{$tier}]...");
            DB::transaction(fn() => $this->seedTenant($catalog, $affinityGroups, $categoryHours, $templates, $tier));
        }

        $this->command->info('Done.');
    }

    // ────────────────────────────────────────────────────────
    // ROLES
    // ────────────────────────────────────────────────────────
    private function seedRoles(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['name' => 'admin',         'description' => 'Full system access',                   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'kitchen_staff', 'description' => 'Order queue and inventory management', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'user',          'description' => 'Browse menu and place orders',         'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    // ────────────────────────────────────────────────────────
    // TENANT TIER SELECTION
    // ────────────────────────────────────────────────────────
    private function pickTenantTier(): string
    {
        $pool = [];
        foreach (self::TENANT_TIERS as $tier => $cfg) {
            for ($w = 0; $w < $cfg['weight']; $w++) {
                $pool[] = $tier;
            }
        }
        return $pool[array_rand($pool)];
    }

    // ────────────────────────────────────────────────────────
    // TENANT ORCHESTRATION
    // ────────────────────────────────────────────────────────
    private function seedTenant(
        array  $catalog,
        array  $affinityGroups,
        array  $categoryHours,
        array  $templates,
        string $tier
    ): void {
        $tierCfg = self::TENANT_TIERS[$tier];

        $tenantName = fake()->company();

        $tenantId = DB::table('tenants')->insertGetId([
            'name'                 => $tenantName,
            'subscription_active'  => fake()->boolean(90),
            'subscription_ends_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        [$adminIds, $staffIds, $userIds] = $this->seedUsers($tenantId, $tenantName, $tierCfg);

        $menuItems = $this->seedCatalog($tenantId, $adminIds[0], $catalog, $tierCfg['price_premium']);

        $this->seedOrders($userIds, $staffIds, $menuItems, $affinityGroups, $categoryHours, $tierCfg['order_multiplier']);

        $this->seedMessages($tenantId, array_merge($adminIds, $staffIds), $templates);
    }

    // ────────────────────────────────────────────────────────
    // USERS
    // Email format: username@tenantdomain
    //   username   = normalized first.last  (lowercase, no specials)
    //   tenantdomain = normalized tenant name + .com
    //   e.g. "Acme & Co." → acme.co.com
    //        user "John O'Brien" → john.obrien@acme.co.com
    // Uniqueness collision handled by appending a numeric suffix.
    // ────────────────────────────────────────────────────────
    private function seedUsers(int $tenantId, string $tenantName, array $tierCfg): array
    {
        $total      = fake()->numberBetween($tierCfg['users_min'], $tierCfg['users_max']);
        $adminCount = max(1, (int) round($total * 0.02));
        $staffCount = max(1, (int) round($total * 0.08));
        $userCount  = $total - $adminCount - $staffCount;

        $tenantDomain  = $this->normalizeDomain($tenantName);
        $usedUsernames = []; // track within this tenant to avoid duplicate local parts
        $records       = [];

        foreach (['admin' => $adminCount, 'kitchen_staff' => $staffCount, 'user' => $userCount] as $role => $count) {
            for ($i = 0; $i < $count; $i++) {
                $name     = fake()->name();
                $username = $this->normalizeUsername($name, $usedUsernames);

                $usedUsernames[$username] = true;

                $ts = fake()->dateTimeBetween('-6 months', 'now');
                $records[] = [
                    'tenant_id'  => $tenantId,
                    'name'       => $name,
                    'email'      => "{$username}@{$tenantDomain}",
                    'password'   => $this->hashedPassword,
                    'role'       => $role,
                    'role_id'    => null,
                    'is_active'  => fake()->boolean(90),
                    'created_at' => $ts,
                    'updated_at' => $ts,
                    'deleted_at' => null,
                ];
            }
        }

        DB::table('users')->insert($records);

        $inserted = DB::table('users')
            ->where('tenant_id', $tenantId)
            ->select('id', 'role')
            ->get();

        return [
            $inserted->where('role', 'admin')->pluck('id')->all(),
            $inserted->where('role', 'kitchen_staff')->pluck('id')->all(),
            $inserted->where('role', 'user')->pluck('id')->all(),
        ];
    }

    // ────────────────────────────────────────────────────────
    // EMAIL HELPERS
    // ────────────────────────────────────────────────────────

    /**
     * Normalize a company name into a valid email domain segment.
     *
     * Steps:
     *  1. Lowercase
     *  2. Remove possessives ('s)
     *  3. Replace any run of non-alphanumeric chars with a single dot
     *  4. Strip leading/trailing dots
     *  5. Collapse multiple consecutive dots
     *  6. Append .com
     *
     * Examples:
     *   "Acme & Co."          → acme.co.com
     *   "O'Brien LLC"         → obrien.llc.com
     *   "Tech-Solutions, Inc" → tech.solutions.inc.com
     */
    private function normalizeDomain(string $companyName): string
    {
        $domain = strtolower($companyName);
        $domain = preg_replace("/['\x{2019}]s\b/u", '', $domain);   // remove possessives
        $domain = preg_replace('/[^a-z0-9]+/', '.', $domain);        // non-alnum → dot
        $domain = trim($domain, '.');                                  // strip edge dots
        $domain = preg_replace('/\.{2,}/', '.', $domain);             // collapse dots
        $domain = $domain ?: 'tenant';                                 // fallback

        return $domain . '.com';
    }

    /**
     * Derive a unique username from a person's full name.
     *
     * Format: firstname.lastname  (both normalized)
     * Normalization:
     *  1. Lowercase
     *  2. Transliterate accented chars where possible (iconv)
     *  3. Remove apostrophes and other punctuation
     *  4. Replace remaining non-alphanumeric with dot
     *  5. Strip/collapse edge and consecutive dots
     *
     * Uniqueness (within the tenant): appends .2, .3, … on collision.
     *
     * Examples:
     *   "John Smith"       → john.smith
     *   "Mary O'Brien"     → mary.obrien
     *   "José García"      → jose.garcia
     *   "Li Wei"           → li.wei
     */
    private function normalizeUsername(string $fullName, array $used): string
    {
        $parts     = preg_split('/\s+/', trim($fullName));
        $firstName = $parts[0] ?? 'user';
        $lastName  = end($parts) ?: 'user';

        $normalize = function (string $part): string {
            // Transliterate Unicode (é→e, ü→u, etc.) if iconv is available
            if (function_exists('iconv')) {
                $part = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part) ?: $part;
            }
            $part = strtolower($part);
            $part = preg_replace("/['\x{2019}]/u", '', $part);   // drop apostrophes
            $part = preg_replace('/[^a-z0-9]+/', '', $part);      // drop everything else
            return $part ?: 'x';
        };

        $base = $normalize($firstName) . '.' . $normalize($lastName);
        $base = preg_replace('/\.{2,}/', '.', $base);
        $base = trim($base, '.');
        $base = $base ?: 'user';

        // Guarantee uniqueness within this tenant
        $candidate = $base;
        $suffix    = 2;
        while (isset($used[$candidate])) {
            $candidate = $base . '.' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    // ────────────────────────────────────────────────────────
    // CATALOG — categories, menu items, stock, stock movements
    //
    // Price premium: large tenants charge 10–20% more, letting AI
    // learn price elasticity patterns across tenant tiers.
    //
    // Returns: array of menu item descriptors
    //   [id, name, price, tier, category]
    // ────────────────────────────────────────────────────────
    private function seedCatalog(int $tenantId, int $adminId, array $catalog, float $pricePremium): array
    {
        $allMenuItems = []; // [{id, name, price, tier, category}]
        $stockMeta    = []; // [{menu_item_id, qty, threshold, ts}]

        foreach ($catalog as $categoryName => $items) {
            $catTs = fake()->dateTimeBetween('-6 months', '-5 months');

            $categoryId = DB::table('categories')->insertGetId([
                'tenant_id'  => $tenantId,
                'name'       => $categoryName,
                'created_at' => $catTs,
                'updated_at' => $catTs,
                'deleted_at' => null,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'deleted_by' => null,
            ]);

            $itemRecords = [];
            foreach ($items as $item) {
                $itemTs    = fake()->dateTimeBetween('-6 months', '-4 months');
                $basePrice = fake()->randomFloat(2, $item['price_min'], $item['price_max']);
                $price     = round($basePrice * (1 + $pricePremium), 2);

                $itemRecords[] = [
                    'category_id'                => $categoryId,
                    'name'                       => $item['name'],
                    'description'                => $item['description'],
                    'image_path'                 => null,
                    'price'                      => $price,
                    'is_available'               => fake()->boolean(85),
                    'needs_restock'              => false,
                    'requested_restock_quantity' => null,
                    'created_at'                 => $itemTs,
                    'updated_at'                 => $itemTs,
                    'deleted_at'                 => null,
                    'created_by'                 => $adminId,
                    'updated_by'                 => $adminId,
                    'deleted_by'                 => null,
                ];
            }

            DB::table('menu_items')->insert($itemRecords);

            $inserted = DB::table('menu_items')
                ->where('category_id', $categoryId)
                ->select('id', 'price', 'name', 'created_at')
                ->get();

            foreach ($inserted as $row) {
                // Find matching catalog item tier by name
                $catalogItem  = collect($items)->firstWhere('name', $row->name);
                $tier         = $catalogItem['tier'] ?? MenuItemFactory::TIER_REGULAR;

                $allMenuItems[] = [
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'price'    => (float) $row->price,
                    'tier'     => $tier,
                    'category' => $categoryName,
                ];
                $stockMeta[] = [
                    'menu_item_id' => $row->id,
                    'tier'         => $tier,
                    'qty'          => fake()->numberBetween(0, 150),
                    'threshold'    => fake()->numberBetween(5, 20),
                    'ts'           => $row->created_at,
                ];
            }
        }

        // ── Stock bulk insert ────────────────────────────────
        $stockRecords = array_map(fn($m) => [
            'menu_item_id'        => $m['menu_item_id'],
            'current_quantity'    => $m['qty'],
            'low_stock_threshold' => $m['threshold'],
            'restock_level'       => fake()->numberBetween(50, 200),
            'created_at'          => $m['ts'],
            'updated_at'          => $m['ts'],
            'deleted_at'          => null,
            'created_by'          => $adminId,
            'updated_by'          => $adminId,
            'deleted_by'          => null,
        ], $stockMeta);

        foreach (array_chunk($stockRecords, self::CHUNK) as $chunk) {
            DB::table('stock')->insert($chunk);
        }

        $menuItemIds = array_column($stockMeta, 'menu_item_id');
        $stockIdMap  = DB::table('stock')
            ->whereIn('menu_item_id', $menuItemIds)
            ->pluck('id', 'menu_item_id');

        // ── Stock movements: realistic time-series ───────────
        // Hot items: high sale frequency, frequent restocks, occasional stockouts.
        // Regular items: moderate sales, restocks when low.
        // Slow items: few sales, rare restocks, occasional damage/adjustment.
        $movementRecords = [];

        foreach ($stockMeta as $m) {
            $stockId  = $stockIdMap[$m['menu_item_id']];
            $tier     = $m['tier'];
            $prev     = $m['qty'];
            $sixMonthsAgo = strtotime('-6 months');
            $now          = time();
            $span         = $now - $sixMonthsAgo;

            // Movement 1: initial stock load at item creation
            $movementRecords[] = [
                'stock_id'          => $stockId,
                'movement_type'     => 'initial',
                'quantity_changed'  => $prev,
                'previous_quantity' => 0,
                'new_quantity'      => $prev,
                'reason'            => 'Initial stock load',
                'created_at'        => $m['ts'],
                'updated_at'        => $m['ts'],
                'deleted_at'        => null,
                'created_by'        => $adminId,
                'updated_by'        => $adminId,
                'deleted_by'        => null,
            ];

            // Movements 2–N: chronological operational movements
            // Timestamps are strictly increasing within each stock item's history.
            $movCount = self::MOVEMENTS_PER_ITEM - 1;

            // Tier controls the type distribution of movements
            $typePool = match ($tier) {
                MenuItemFactory::TIER_HOT     => array_merge(
                    array_fill(0, 10, 'sale'),
                    array_fill(0, 4,  'restock'),
                    array_fill(0, 1,  'damage'),
                    array_fill(0, 1,  'adjustment')
                ),
                MenuItemFactory::TIER_REGULAR => array_merge(
                    array_fill(0, 5,  'sale'),
                    array_fill(0, 3,  'restock'),
                    array_fill(0, 1,  'damage'),
                    array_fill(0, 1,  'adjustment')
                ),
                default /* SLOW */            => array_merge(
                    array_fill(0, 2,  'sale'),
                    array_fill(0, 1,  'restock'),
                    array_fill(0, 2,  'damage'),
                    array_fill(0, 2,  'adjustment')
                ),
            };

            // Spread timestamps evenly with small jitter so they're strictly sequential
            $interval = (int) ($span / ($movCount + 1));

            for ($x = 1; $x <= $movCount; $x++) {
                $movType = $typePool[array_rand($typePool)];

                // Hot items deplete faster, slow items deplete slowly
                $saleMagnitude = match ($tier) {
                    MenuItemFactory::TIER_HOT     => fake()->numberBetween(5, max(5, min($prev, 20))),
                    MenuItemFactory::TIER_REGULAR => fake()->numberBetween(2, max(2, min($prev, 12))),
                    default                       => fake()->numberBetween(1, max(1, min($prev, 5))),
                };

                $changed = match ($movType) {
                    'sale'       => -$saleMagnitude,
                    'damage'     => -fake()->numberBetween(1, max(1, min($prev, 5))),
                    'restock'    => fake()->numberBetween(20, 80),
                    'return'     => fake()->numberBetween(1, 10),
                    'adjustment' => fake()->numberBetween(-8, 8),
                    default      => 0,
                };

                $new = max(0, $prev + $changed);

                // If stock hits zero, force a restock on the next movement
                if ($new === 0 && $x < $movCount) {
                    $movType = 'restock';
                    $changed = fake()->numberBetween(30, 100);
                    $new     = $prev + $changed;
                }

                $movTs = date('Y-m-d H:i:s', $sixMonthsAgo + ($interval * $x) + fake()->numberBetween(0, (int)($interval * 0.5)));

                $movementRecords[] = [
                    'stock_id'          => $stockId,
                    'movement_type'     => $movType,
                    'quantity_changed'  => $changed,
                    'previous_quantity' => $prev,
                    'new_quantity'      => $new,
                    'reason'            => fake()->optional(0.4)->sentence(),
                    'created_at'        => $movTs,
                    'updated_at'        => $movTs,
                    'deleted_at'        => null,
                    'created_by'        => $adminId,
                    'updated_by'        => $adminId,
                    'deleted_by'        => null,
                ];

                $prev = $new;
            }
        }

        foreach (array_chunk($movementRecords, self::CHUNK) as $chunk) {
            DB::table('stock_movements')->insert($chunk);
        }

        return $allMenuItems;
    }

    // ────────────────────────────────────────────────────────
    // ORDERS, ORDER ITEMS, TRANSACTIONS
    //
    // Patterns embedded:
    //   1. Weighted item selection by popularity tier
    //   2. Basket affinity — companions injected alongside anchors
    //   3. Time-of-day peaks (lunch 12–14, dinner 19–21)
    //   4. Category-restricted hours (breakfast only 7–11, etc.)
    //   5. Day-of-week seasonality (Fri/Sat surge)
    //   6. 6-month growth ramp (volume increases over time)
    //   7. Tenant size multiplier (large tenants = more orders)
    // ────────────────────────────────────────────────────────
    private function seedOrders(
        array  $userIds,
        array  $staffIds,
        array  $menuItems,
        array  $affinityGroups,
        array  $categoryHours,
        float  $orderMultiplier
    ): void {
        if (empty($menuItems)) return;

        // ── Build weighted item pool ─────────────────────────
        // Each item appears N times in $weightedPool according to its tier weight.
        // weightedPick() draws from this array for fast O(n) weighted selection.
        $weightedPool = [];
        $itemByName   = []; // name → item descriptor (for affinity lookup)

        foreach ($menuItems as $item) {
            $w = match ($item['tier']) {
                MenuItemFactory::TIER_HOT     => 5,
                MenuItemFactory::TIER_REGULAR => 1,
                MenuItemFactory::TIER_SLOW    => 0, // handled below
            };
            // SLOW items get weight 0.3 — approximate by adding 1 entry per ~3 items
            if ($item['tier'] === MenuItemFactory::TIER_SLOW) {
                if (fake()->boolean(33)) { // ~33% chance → effective weight ~0.33
                    $weightedPool[] = $item;
                }
            } else {
                for ($w2 = 0; $w2 < $w; $w2++) {
                    $weightedPool[] = $item;
                }
            }
            $itemByName[$item['name']] = $item;
        }

        // Safety: if all items were SLOW and none made it into the pool
        if (empty($weightedPool)) {
            $weightedPool = $menuItems;
        }

        // ── Step 1: Build all order rows ─────────────────────
        $orderRows = [];
        $sixMonthsAgo = strtotime('-6 months');

        foreach ($userIds as $userId) {
            $baseCount = fake()->numberBetween(
                (int)(self::ORDERS_MIN_PER_USER * $orderMultiplier),
                (int)(self::ORDERS_MAX_PER_USER * $orderMultiplier)
            );

            for ($o = 0; $o < $baseCount; $o++) {
                // Pick a random month (0–5) and apply growth ramp
                $monthIdx = fake()->numberBetween(0, 5);
                $ramp     = self::GROWTH_RAMP[$monthIdx];
                if (!fake()->boolean((int)($ramp * 100))) {
                    continue; // growth ramp drops some orders from early months
                }

                $status = fake()->randomElement([
                    'served', 'served', 'served', 'served',
                    'pending', 'preparing', 'ready', 'canceled',
                ]);

                $ts = $this->realisticTimestamp($monthIdx);

                $orderRows[] = [
                    'user_id'      => $userId,
                    'status'       => $status,
                    'total_amount' => 0,
                    'notes'        => fake()->optional(0.3)->sentence(),
                    'created_at'   => $ts,
                    'updated_at'   => $ts,
                ];
            }
        }

        if (empty($orderRows)) return;

        foreach (array_chunk($orderRows, self::CHUNK) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        // ── Step 2: Query back inserted orders ───────────────
        $orders = DB::table('orders')
            ->whereIn('user_id', $userIds)
            ->where('total_amount', 0)
            ->select('id', 'status', 'created_at')
            ->get();

        // ── Step 3: Build order_items with affinity logic ─────
        $orderItemRows   = [];
        $orderTotals     = [];
        $transactionRows = [];

        foreach ($orders as $order) {
            $orderHour = (int) date('G', strtotime($order->created_at));

            // Filter menu items whose category is allowed at this hour
            $hourFiltered = array_values(array_filter($menuItems, function ($item) use ($orderHour, $categoryHours) {
                $hours = $categoryHours[$item['category']] ?? [0, 23];
                return $orderHour >= $hours[0] && $orderHour <= $hours[1];
            }));

            // Rebuild weighted pool for this hour's eligible items
            $hourPool = [];
            foreach ($hourFiltered as $item) {
                $w = match ($item['tier']) {
                    MenuItemFactory::TIER_HOT     => 5,
                    MenuItemFactory::TIER_REGULAR => 1,
                    default                       => (fake()->boolean(33) ? 1 : 0),
                };
                for ($w2 = 0; $w2 < $w; $w2++) {
                    $hourPool[] = $item;
                }
            }
            if (empty($hourPool)) {
                $hourPool = !empty($hourFiltered) ? $hourFiltered : $menuItems;
            }

            // Pick 1–3 anchor items (weighted)
            $anchorCount  = fake()->numberBetween(1, 3);
            $pickedIds    = [];
            $pickedItems  = [];

            for ($p = 0; $p < $anchorCount; $p++) {
                $candidate = $hourPool[array_rand($hourPool)];
                if (!in_array($candidate['id'], $pickedIds)) {
                    $pickedIds[]  = $candidate['id'];
                    $pickedItems[] = $candidate;
                }
            }

            // Inject affinity companions
            foreach ($pickedItems as $anchor) {
                $companions = $affinityGroups[$anchor['name']] ?? [];
                foreach ($companions as $companionName) {
                    if (!isset($itemByName[$companionName])) continue;
                    if (in_array($itemByName[$companionName]['id'], $pickedIds)) continue;
                    if (fake()->boolean((int)(self::AFFINITY_PROB * 100))) {
                        $comp         = $itemByName[$companionName];
                        $pickedIds[]  = $comp['id'];
                        $pickedItems[] = $comp;
                    }
                }
            }

            // Build order_item rows and accumulate total
            $total = 0;
            foreach ($pickedItems as $item) {
                $qty    = fake()->numberBetween(1, 4);
                $total += $qty * $item['price'];

                $orderItemRows[] = [
                    'order_id'     => $order->id,
                    'menu_item_id' => $item['id'],
                    'quantity'     => $qty,
                    'unit_price'   => $item['price'],
                    'created_at'   => $order->created_at,
                    'updated_at'   => $order->created_at,
                ];
            }

            $orderTotals[$order->id] = round($total, 2);

            // Transaction for served orders only
            if ($order->status === 'served' && !empty($staffIds)) {
                $tendered = $total + fake()->randomFloat(2, 0, 50);
                $transactionRows[] = [
                    'order_id'        => $order->id,
                    'recorded_by'     => fake()->randomElement($staffIds),
                    'tendered_amount' => round($tendered, 2),
                    'change_returned' => round($tendered - $total, 2),
                    'created_at'      => $order->created_at,
                    'updated_at'      => $order->created_at,
                ];
            }
        }

        // ── Step 4: Bulk insert order_items ──────────────────
        foreach (array_chunk($orderItemRows, self::CHUNK) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }

        // ── Step 5: Single SQL to update all order totals ─────
        if (!empty($orderTotals)) {
            $cases = '';
            foreach ($orderTotals as $orderId => $total) {
                $cases .= "WHEN {$orderId} THEN {$total} ";
            }
            $ids = implode(',', array_keys($orderTotals));
            DB::statement("UPDATE orders SET total_amount = CASE id {$cases} END WHERE id IN ({$ids})");
        }

        // ── Step 6: Bulk insert transactions ─────────────────
        foreach (array_chunk($transactionRows, self::CHUNK) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }
    }

    // ────────────────────────────────────────────────────────
    // MESSAGES
    // ────────────────────────────────────────────────────────
    private function seedMessages(int $tenantId, array $staffAndAdminIds, array $templates): void
    {
        $tagKeys = array_keys($templates);
        $records = [];

        for ($m = 0; $m < self::MESSAGES_PER_TENANT; $m++) {
            $tag        = fake()->randomElement($tagKeys);
            $template   = fake()->randomElement($templates[$tag]);
            $senderId   = fake()->randomElement($staffAndAdminIds);
            $pool       = array_values(array_filter($staffAndAdminIds, fn($id) => $id !== $senderId));
            $receiverId = fake()->randomElement(empty($pool) ? $staffAndAdminIds : $pool);
            $ts         = fake()->dateTimeBetween('-6 months', 'now');

            $records[] = [
                'tenant_id'   => $tenantId,
                'sender_id'   => $senderId,
                'receiver_id' => $receiverId,
                'title'       => $template['title'],
                'content'     => $template['content'],
                'tag'         => $tag,
                'priority'    => fake()->randomElement(['high', 'medium', 'medium', 'low', 'low']),
                'is_read'     => fake()->boolean(60),
                'created_at'  => $ts,
                'updated_at'  => $ts,
            ];
        }

        foreach (array_chunk($records, self::CHUNK) as $chunk) {
            DB::table('messages')->insert($chunk);
        }
    }

    // ────────────────────────────────────────────────────────
    // HELPERS
    // ────────────────────────────────────────────────────────

    /**
     * Generate a realistic order timestamp for a given month index (0=oldest).
     *
     * Algorithm:
     *   1. Pick a date within the correct month (6-month window split into 6 buckets).
     *   2. Pick a day within that date and apply DOW weight — reject & retry if
     *      a random float exceeds the DOW weight (acceptance sampling).
     *   3. Pick an hour using acceptance sampling against HOD_WEIGHTS.
     *   4. Add random minutes + seconds.
     */
    private function realisticTimestamp(int $monthIdx): string
    {
        $sixMonthsAgo = strtotime('-6 months');
        $now          = time();
        $span         = $now - $sixMonthsAgo;
        $sliceSize    = (int) ($span / 6);

        $sliceStart = $sixMonthsAgo + ($monthIdx * $sliceSize);
        $sliceEnd   = min($now, $sliceStart + $sliceSize);

        // Pick a day with DOW acceptance sampling (max 30 attempts → fallback)
        $maxDow = max(self::DOW_WEIGHTS);
        $dayTs  = null;
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $candidate = fake()->numberBetween($sliceStart, $sliceEnd);
            $dow       = (int) date('w', $candidate); // 0=Sun
            if (fake()->randomFloat(2, 0, $maxDow) <= self::DOW_WEIGHTS[$dow]) {
                $dayTs = $candidate;
                break;
            }
        }
        $dayTs = $dayTs ?? fake()->numberBetween($sliceStart, $sliceEnd);

        // Pick an hour with HOD acceptance sampling
        $maxHod = max(self::HOD_WEIGHTS);
        $hour   = null;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidateHour = fake()->numberBetween(7, 22);
            if (fake()->randomFloat(2, 0, $maxHod) <= self::HOD_WEIGHTS[$candidateHour]) {
                $hour = $candidateHour;
                break;
            }
        }
        $hour = $hour ?? fake()->numberBetween(11, 21);

        $minute = fake()->numberBetween(0, 59);
        $second = fake()->numberBetween(0, 59);

        return date('Y-m-d', $dayTs) . sprintf(' %02d:%02d:%02d', $hour, $minute, $second);
    }
}