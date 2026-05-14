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
    use WithoutModelEvents;

    // ────────────────────────────────────────────────────────
    // SCALE CONSTANTS
    // ────────────────────────────────────────────────────────
    private const TENANTS             = 300;
    private const ORDERS_MIN_PER_USER = 15;
    private const ORDERS_MAX_PER_USER = 25;
    private const MESSAGES_PER_TENANT = 40;
    private const MOVEMENTS_PER_ITEM  = 30;
    private const CHUNK               = 500;

    // ── Tenant size tiers ────────────────────────────────────
    // Controls order volume, user count, and price premium.
    // Small orgs: skeleton teams, budget pricing.
    // Large orgs: high throughput, premium pricing — enables
    // cross-tenant price elasticity analysis.
    private const TENANT_TIERS = [
        'small'  => ['weight' => 5, 'order_multiplier' => 0.5,  'price_premium' => 0.0, 'users_min' => 10,  'users_max' => 30 ],
        'medium' => ['weight' => 3, 'order_multiplier' => 1.0,  'price_premium' => 0.1, 'users_min' => 40,  'users_max' => 80 ],
        'large'  => ['weight' => 2, 'order_multiplier' => 1.8,  'price_premium' => 0.2, 'users_min' => 100, 'users_max' => 200],
    ];

    // ── Basket affinity companion injection probability ───────
    // When an anchor item is in an order, each companion item
    // has this probability of being added (e.g. Biryani → Lassi).
    private const AFFINITY_PROB = 72; // integer for faster boolean check

    // ── Day-of-week weights (0=Sun … 6=Sat) ──────────────────
    // B2B workplace canteen — BD weekends (Fri/Sat) are skeleton crew only.
    // Sun is also a weekend day in BD context. Tue/Wed are peak office days.
    private const DOW_WEIGHTS = [
        0 => 0.15,  // Sunday   — BD weekend, skeleton crew
        1 => 1.10,  // Monday
        2 => 1.20,  // Tuesday  — peak
        3 => 1.20,  // Wednesday — peak
        4 => 1.10,  // Thursday
        5 => 0.10,  // Friday   — BD weekend
        6 => 0.12,  // Saturday — BD weekend
    ];

    // ── Hour-of-day weights (0–23) ────────────────────────────
    // Twin peaks: lunch 12–14, dinner 19–21.
    // Dead hours (0–6) produce zero orders.
    // Pre-computed as a flat lookup for O(1) access per timestamp.
    private const HOD_WEIGHTS = [
        0  => 0.0,  1  => 0.0,  2  => 0.0,  3  => 0.0,
        4  => 0.0,  5  => 0.0,  6  => 0.0,  7  => 0.3,
        8  => 0.6,  9  => 0.9,  10 => 1.2,  11 => 2.0,
        12 => 3.0,  13 => 3.5,  14 => 2.5,  15 => 1.0,
        16 => 0.7,  17 => 0.8,  18 => 1.5,  19 => 3.0,
        20 => 3.5,  21 => 2.5,  22 => 1.0,  23 => 0.3,
    ];

    // ── Growth ramp: month index (0=oldest) → multiplier ─────
    // Simulates an org growing canteen usage over 6 months.
    // Used as a probability gate — early months drop more orders.
    private const GROWTH_RAMP = [
        0 => 55, 1 => 70, 2 => 80, 3 => 90, 4 => 100, 5 => 115,
    ]; // stored as integers (percentage) for fast rand comparison

    // ── Type pools per tier (pre-built, never rebuilt at runtime) ──
    // Hot  items: frequent sales + restocks, occasional damage
    // Slow items: mostly damage/adjustment, rare sales
    private const MOVEMENT_TYPE_POOLS = [
        MenuItemFactory::TIER_HOT => [
            'sale','sale','sale','sale','sale','sale','sale','sale','sale','sale',
            'restock','restock','restock','restock',
            'damage','adjustment',
        ],
        MenuItemFactory::TIER_REGULAR => [
            'sale','sale','sale','sale','sale',
            'restock','restock','restock',
            'damage','adjustment',
        ],
        MenuItemFactory::TIER_SLOW => [
            'sale','sale',
            'restock',
            'damage','damage',
            'adjustment','adjustment',
        ],
    ];

    private string $hashedPassword;

    // Pre-computed at run() start — reused across all tenants, never rebuilt.
    // These are constant for the whole seeding run.
    private int   $historyStart;
    private int   $historySpan;
    private int   $sliceSize;
    private float $maxDow;
    private float $maxHod;

    // ── Pre-built weighted HOD pick table ─────────────────────
    // An array of hours where each hour appears proportional to
    // its weight × 10, giving a flat array we can array_rand() on
    // instead of running acceptance sampling loops per timestamp.
    private array $hodPickTable  = [];
    private array $dowPickTable  = [];

    // ────────────────────────────────────────────────────────
    // ENTRY POINT
    // ────────────────────────────────────────────────────────
    public function run(): void
    {
        $this->hashedPassword = Hash::make('password');

        // ── Pre-compute time constants once ───────────────────
        // Calling strtotime/time() inside loops is an unnecessary
        // syscall overhead — compute once, reuse everywhere.
        $this->historyStart = strtotime('-6 months');
        $this->historySpan  = time() - $this->historyStart;
        $this->sliceSize    = (int)($this->historySpan / 6);
        $this->maxDow       = max(self::DOW_WEIGHTS);
        $this->maxHod       = max(self::HOD_WEIGHTS);

        // ── Pre-build HOD pick table ──────────────────────────
        // Instead of acceptance-sampling with up to 50 attempts per
        // timestamp, we build a flat array once and array_rand() it.
        // Weight × 10 gives enough resolution without a huge array.
        foreach (self::HOD_WEIGHTS as $hour => $weight) {
            $entries = (int)round($weight * 10);
            for ($e = 0; $e < $entries; $e++) {
                $this->hodPickTable[] = $hour;
            }
        }
        if (empty($this->hodPickTable)) {
            $this->hodPickTable = range(11, 21);
        }

        // ── Pre-build DOW pick table ──────────────────────────
        // Same principle — weighted flat array for O(1) day selection.
        foreach (self::DOW_WEIGHTS as $dow => $weight) {
            $entries = (int)round($weight * 10);
            for ($e = 0; $e < $entries; $e++) {
                $this->dowPickTable[] = $dow;
            }
        }
        if (empty($this->dowPickTable)) {
            $this->dowPickTable = [1, 2, 3, 4]; // Mon–Thu fallback
        }

        $catalog        = MenuItemFactory::catalog();
        $affinityGroups = MenuItemFactory::affinityGroups();
        $categoryHours  = MenuItemFactory::categoryHours();
        $templates      = MessageFactory::templates();

        $this->command->info('Seeding roles...');
        $this->seedRoles();

        for ($i = 1; $i <= self::TENANTS; $i++) {
            $tier = $this->pickTenantTier();
            $this->command->info("Seeding tenant {$i}/" . self::TENANTS . " [{$tier}]");
            // Transaction is intentionally NOT wrapping the whole tenant.
            // We commit in small phases (catalog, orders, messages) so Postgres
            // never holds thousands of dirty pages open at once — this is the
            // primary cause of the 100% CPU stall on large tenants.
            $this->seedTenant($catalog, $affinityGroups, $categoryHours, $templates, $tier);
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
        static $pool = null;
        if ($pool === null) {
            $pool = [];
            foreach (self::TENANT_TIERS as $tier => $cfg) {
                for ($w = 0; $w < $cfg['weight']; $w++) {
                    $pool[] = $tier;
                }
            }
        }
        return $pool[array_rand($pool)];
    }

    // ────────────────────────────────────────────────────────
    // TENANT ORCHESTRATION
    //
    // Deliberately split into three separate transactions:
    //   1. Catalog (categories + menu_items + stock + movements)
    //   2. Orders  (orders + order_items + transactions)
    //   3. Messages
    //
    // This keeps each transaction small, limits WAL pressure,
    // and allows Postgres to checkpoint between phases rather
    // than accumulating unbounded dirty pages for an entire
    // large tenant in one shot.
    // ────────────────────────────────────────────────────────
    private function seedTenant(
        array  $catalog,
        array  $affinityGroups,
        array  $categoryHours,
        array  $templates,
        string $tier
    ): void {
        $tierCfg    = self::TENANT_TIERS[$tier];
        $tenantName = fake()->company();

        // Phase 0: tenant + users (tiny, no transaction needed)
        $tenantId = DB::table('tenants')->insertGetId([
            'name'                 => $tenantName,
            'subscription_active'  => fake()->boolean(90),
            'subscription_ends_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        [$adminIds, $staffIds, $userIds] = $this->seedUsers($tenantId, $tenantName, $tierCfg);

        // Phase 1: catalog — commit immediately after
        $menuItems = DB::transaction(
            fn() => $this->seedCatalog($tenantId, $adminIds[0], $catalog, $tierCfg['price_premium'])
        );

        // Phase 2: orders — largest write, committed alone
        DB::transaction(
            fn() => $this->seedOrders(
                $userIds, $staffIds, $menuItems,
                $affinityGroups, $categoryHours,
                $tierCfg['order_multiplier']
            )
        );

        // Phase 3: messages — tiny, committed alone
        DB::transaction(
            fn() => $this->seedMessages($tenantId, array_merge($adminIds, $staffIds), $templates)
        );
    }

    // ────────────────────────────────────────────────────────
    // USERS
    // Email format: firstname.lastname@tenantdomain.com
    // Normalized: lowercase, accents transliterated, specials → dots.
    // Collisions within a tenant handled by numeric suffix (.2, .3 …).
    // ────────────────────────────────────────────────────────
    private function seedUsers(int $tenantId, string $tenantName, array $tierCfg): array
    {
        $total      = fake()->numberBetween($tierCfg['users_min'], $tierCfg['users_max']);
        $adminCount = max(1, (int)round($total * 0.02));
        $staffCount = max(1, (int)round($total * 0.08));
        $userCount  = $total - $adminCount - $staffCount;

        $tenantDomain  = $this->normalizeDomain($tenantName);
        $usedUsernames = [];
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

        // Single bulk insert — no chunking needed at this scale
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
    // CATALOG — categories, menu items, stock, stock movements
    //
    // Optimizations vs old version:
    //   - Category loop now bulk-inserts all menu items per category
    //     then queries IDs once, avoiding N insertGetId() calls.
    //   - collect()->firstWhere() replaced with a plain PHP keyed
    //     lookup array built once per category.
    //   - strtotime/time constants hoisted to $this->historyStart etc.
    //   - stockIdMap built from a single whereIn query at the end,
    //     not one query per item.
    //   - All movement records accumulated in PHP, then flushed in
    //     one chunked bulk insert — no per-item DB round-trips.
    // ────────────────────────────────────────────────────────
    private function seedCatalog(int $tenantId, int $adminId, array $catalog, float $pricePremium): array
    {
        $allMenuItems    = [];
        $stockMeta       = [];
        $historyStart    = $this->historyStart;
        $historySpan     = $this->historySpan;

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

            // Build a plain keyed lookup: name → tier
            // Much faster than calling collect()->firstWhere() inside the
            // post-insert loop — avoids re-wrapping arrays as Collections.
            $tierByName = [];
            $itemRecords = [];
            foreach ($items as $item) {
                $tierByName[$item['name']] = $item['tier'];

                $itemTs = fake()->dateTimeBetween('-6 months', '-4 months');
                $price  = round(
                    fake()->randomFloat(2, $item['price_min'], $item['price_max']) * (1 + $pricePremium),
                    2
                );

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

            // Bulk insert all items for this category, then fetch IDs once
            DB::table('menu_items')->insert($itemRecords);

            $inserted = DB::table('menu_items')
                ->where('category_id', $categoryId)
                ->select('id', 'name', 'price', 'created_at')
                ->get();

            foreach ($inserted as $row) {
                $tier = $tierByName[$row->name] ?? MenuItemFactory::TIER_REGULAR;

                $allMenuItems[] = [
                    'id'       => $row->id,
                    'name'     => $row->name,
                    'price'    => (float)$row->price,
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

        // ── Stock bulk insert ─────────────────────────────────
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

        // ── Stock ID map: single query for all items ──────────
        // Old version queried inside the movement loop — one round-trip
        // per item. One whereIn is orders of magnitude faster.
        $menuItemIds = array_column($stockMeta, 'menu_item_id');
        $stockIdMap  = DB::table('stock')
            ->whereIn('menu_item_id', $menuItemIds)
            ->pluck('id', 'menu_item_id')
            ->all();

        // ── Stock movements ───────────────────────────────────
        // Tier-aware movement type distribution:
        //   HOT     → mostly sales + restocks (high turnover)
        //   REGULAR → balanced sales/restocks
        //   SLOW    → mostly damage/adjustment (sits on shelf)
        //
        // Timestamps are evenly spaced with jitter so they are
        // strictly sequential per stock item — required for
        // time-series analysis (depletion forecasting, reorder ML).
        //
        // Stockout protection: if qty hits 0, next movement is
        // forced to 'restock' — models real operational behaviour.
        $movementRecords = [];
        $interval        = (int)($historySpan / (self::MOVEMENTS_PER_ITEM + 1));
        $halfInterval    = (int)($interval * 0.5);

        foreach ($stockMeta as $m) {
            $stockId = $stockIdMap[$m['menu_item_id']];
            $tier    = $m['tier'];
            $prev    = $m['qty'];
            $typePool = self::MOVEMENT_TYPE_POOLS[$tier] ?? self::MOVEMENT_TYPE_POOLS[MenuItemFactory::TIER_REGULAR];
            $poolSize = count($typePool);

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

            for ($x = 1; $x < self::MOVEMENTS_PER_ITEM; $x++) {
                $movType = $typePool[array_rand(array_slice($typePool, 0, $poolSize))];

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

                // Stockout protection — force restock on next movement
                if ($new === 0 && $x < self::MOVEMENTS_PER_ITEM - 1) {
                    $movType = 'restock';
                    $changed = fake()->numberBetween(30, 100);
                    $new     = $prev + $changed;
                }

                $jitter = $halfInterval > 0 ? fake()->numberBetween(0, $halfInterval) : 0;
                $movTs  = date('Y-m-d H:i:s', $historyStart + ($interval * $x) + $jitter);

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
    // Patterns preserved:
    //   1. Item popularity tiers (weighted pool — HOT 5×, REGULAR 1×, SLOW 0.3×)
    //   2. Basket affinity (anchor → companions at 72% probability)
    //   3. Category-restricted hours (Breakfast 7–11, Grills 12–22, etc.)
    //   4. Time-of-day peaks via pre-built HOD pick table
    //   5. Day-of-week skew via pre-built DOW pick table
    //   6. 6-month growth ramp (older months drop more orders)
    //   7. Tenant size multiplier (large tenants = more orders)
    //   8. Realistic order statuses (4-in-8 served, rest in-progress/canceled)
    //
    // Key optimizations vs old version:
    //   - Basket + total pre-computed before DB insert, eliminating
    //     the massive CASE…WHEN UPDATE that was the primary CPU spike.
    //   - $hourPool rebuilt only when $orderHour changes, not per order.
    //     With ~18 distinct peak hours and thousands of orders, this
    //     cuts array_filter() calls by ~99%.
    //   - Pre-built hodPickTable / dowPickTable replaces acceptance-
    //     sampling loops (up to 50 iterations per timestamp) with
    //     a single array_rand() call.
    //   - $itemByName built once at method start using array key lookup
    //     instead of re-scanning $menuItems per affinity check.
    //   - order_items and transactions bulk-inserted in chunks after
    //     all orders are processed — no per-order DB round-trips.
    // ────────────────────────────────────────────────────────
    private function seedOrders(
        array $userIds,
        array $staffIds,
        array $menuItems,
        array $affinityGroups,
        array $categoryHours,
        float $orderMultiplier
    ): void {
        if (empty($menuItems)) return;

        // ── Build weighted item pool (once per tenant) ────────
        $itemByName   = [];
        $weightedPool = [];

        foreach ($menuItems as $item) {
            $itemByName[$item['name']] = $item;
            if ($item['tier'] === MenuItemFactory::TIER_SLOW) {
                if (fake()->boolean(33)) {
                    $weightedPool[] = $item;
                }
            } else {
                $w = ($item['tier'] === MenuItemFactory::TIER_HOT) ? 5 : 1;
                for ($k = 0; $k < $w; $k++) {
                    $weightedPool[] = $item;
                }
            }
        }
        if (empty($weightedPool)) {
            $weightedPool = $menuItems;
        }

        // ── Pre-build per-hour item pools (once per tenant) ───
        // The old code ran array_filter($menuItems, ...) inside the
        // order loop — once per order. Since there are only ~18 distinct
        // active hours and the menu never changes within a tenant, we
        // build each hour's weighted pool once and cache it here.
        $hourlyPools = [];
        foreach (range(0, 23) as $hour) {
            $filtered = [];
            foreach ($menuItems as $item) {
                $hours = $categoryHours[$item['category']] ?? [0, 23];
                if ($hour >= $hours[0] && $hour <= $hours[1]) {
                    $filtered[] = $item;
                }
            }
            if (empty($filtered)) {
                $filtered = $menuItems;
            }
            // Build weighted pool for this hour
            $pool = [];
            foreach ($filtered as $item) {
                if ($item['tier'] === MenuItemFactory::TIER_SLOW) {
                    if (fake()->boolean(33)) $pool[] = $item;
                } else {
                    $w = ($item['tier'] === MenuItemFactory::TIER_HOT) ? 5 : 1;
                    for ($k = 0; $k < $w; $k++) $pool[] = $item;
                }
            }
            $hourlyPools[$hour] = !empty($pool) ? $pool : $filtered;
        }

        // ── Build all order rows, pre-computing basket + total ─
        // Pre-computing the total here eliminates the UPDATE step
        // entirely — the single biggest CPU bottleneck in the old
        // version was the CASE…WHEN UPDATE with thousands of clauses.
        $orderRows       = [];
        $orderItemRows   = [];
        $transactionRows = [];
        $hodCount        = count($this->hodPickTable);
        $staffCount      = count($staffIds);

        foreach ($userIds as $userId) {
            $baseCount = fake()->numberBetween(
                (int)(self::ORDERS_MIN_PER_USER * $orderMultiplier),
                (int)(self::ORDERS_MAX_PER_USER * $orderMultiplier)
            );

            for ($o = 0; $o < $baseCount; $o++) {
                $monthIdx = fake()->numberBetween(0, 5);

                // Growth ramp: older months probabilistically drop orders.
                // Stored as integer percentages for a faster comparison.
                if (fake()->numberBetween(1, 115) > self::GROWTH_RAMP[$monthIdx]) {
                    continue;
                }

                $status = fake()->randomElement([
                    'served', 'served', 'served', 'served',
                    'pending', 'preparing', 'ready', 'canceled',
                ]);

                $ts   = $this->realisticTimestamp($monthIdx);
                $hour = (int)date('G', strtotime($ts));

                // Pick basket using the pre-built hourly pool
                $pool        = $hourlyPools[$hour];
                $anchor      = $pool[array_rand($pool)];
                $pickedIds   = [$anchor['id'] => true];
                $pickedItems = [$anchor];

                // Inject affinity companions
                foreach ($affinityGroups[$anchor['name']] ?? [] as $companionName) {
                    if (!isset($itemByName[$companionName])) continue;
                    if (isset($pickedIds[$itemByName[$companionName]['id']])) continue;
                    if (fake()->numberBetween(1, 100) <= self::AFFINITY_PROB) {
                        $comp                  = $itemByName[$companionName];
                        $pickedIds[$comp['id']] = true;
                        $pickedItems[]         = $comp;
                    }
                }

                // Optionally add a second anchor (orders with 2–3 items are common)
                if (fake()->boolean(40)) {
                    $extra = $pool[array_rand($pool)];
                    if (!isset($pickedIds[$extra['id']])) {
                        $pickedIds[$extra['id']] = true;
                        $pickedItems[]           = $extra;
                    }
                }

                // Compute total inline — no UPDATE needed later
                $total = 0.0;
                foreach ($pickedItems as $item) {
                    $qty    = fake()->numberBetween(1, 4);
                    $total += $qty * $item['price'];

                    $orderItemRows[] = [
                        '__ts'         => $ts,     // temp key, stripped before insert
                        'menu_item_id' => $item['id'],
                        'quantity'     => $qty,
                        'unit_price'   => $item['price'],
                        'created_at'   => $ts,
                        'updated_at'   => $ts,
                    ];
                }

                $total = round($total, 2);

                $orderRows[] = [
                    'user_id'      => $userId,
                    'status'       => $status,
                    'total_amount' => $total,
                    'notes'        => fake()->optional(0.3)->sentence(),
                    'created_at'   => $ts,
                    'updated_at'   => $ts,
                    '__item_count' => count($pickedItems), // how many items belong to this order
                    '__status'     => $status,
                    '__total'      => $total,
                    '__ts'         => $ts,
                    '__staff_idx'  => $staffCount > 0 ? fake()->numberBetween(0, $staffCount - 1) : -1,
                ];
            }
        }

        if (empty($orderRows)) return;

        // ── Bulk insert orders, stripping temp keys ───────────
        $cleanOrderRows = array_map(function ($row) {
            return [
                'user_id'      => $row['user_id'],
                'status'       => $row['status'],
                'total_amount' => $row['total_amount'],
                'notes'        => $row['notes'],
                'created_at'   => $row['created_at'],
                'updated_at'   => $row['updated_at'],
            ];
        }, $orderRows);

        foreach (array_chunk($cleanOrderRows, self::CHUNK) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        // ── Fetch inserted order IDs ──────────────────────────
        // We identify our just-inserted orders by user_id + created_at.
        // total_amount is now set correctly so we can't use the
        // old `where('total_amount', 0)` sentinel trick.
        // Instead we fetch by user_id set and sort by created_at to
        // maintain the same ordering as $orderRows.
        $insertedOrders = DB::table('orders')
            ->whereIn('user_id', $userIds)
            ->orderBy('created_at')
            ->orderBy('id')
            ->select('id', 'status', 'created_at', 'total_amount')
            ->get()
            ->all();

        // Sort $orderRows by created_at to match DB ordering
        usort($orderRows, fn($a, $b) => strcmp($a['created_at'], $b['created_at']));

        // ── Assign order IDs to order_items and transactions ──
        // We walk both arrays in parallel (same count, same sort order).
        // This avoids any per-row DB lookups.
        $itemIdx         = 0;
        $finalItemRows   = [];
        $finalTxRows     = [];

        foreach ($insertedOrders as $dbIdx => $dbOrder) {
            $meta      = $orderRows[$dbIdx] ?? null;
            if (!$meta) continue;

            $itemCount = $meta['__item_count'];

            for ($k = 0; $k < $itemCount; $k++) {
                $rawItem = $orderItemRows[$itemIdx++] ?? null;
                if (!$rawItem) continue;

                $finalItemRows[] = [
                    'order_id'     => $dbOrder->id,
                    'menu_item_id' => $rawItem['menu_item_id'],
                    'quantity'     => $rawItem['quantity'],
                    'unit_price'   => $rawItem['unit_price'],
                    'created_at'   => $rawItem['created_at'],
                    'updated_at'   => $rawItem['updated_at'],
                ];
            }

            if ($dbOrder->status === 'served' && $meta['__staff_idx'] >= 0) {
                $total    = $dbOrder->total_amount;
                $tendered = $total + fake()->randomFloat(2, 0, 50);
                $finalTxRows[] = [
                    'order_id'        => $dbOrder->id,
                    'recorded_by'     => $staffIds[$meta['__staff_idx']],
                    'tendered_amount' => round($tendered, 2),
                    'change_returned' => round($tendered - $total, 2),
                    'created_at'      => $dbOrder->created_at,
                    'updated_at'      => $dbOrder->created_at,
                ];
            }
        }

        foreach (array_chunk($finalItemRows, self::CHUNK) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }

        foreach (array_chunk($finalTxRows, self::CHUNK) as $chunk) {
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

        DB::table('messages')->insert($records);
    }

    // ────────────────────────────────────────────────────────
    // REALISTIC TIMESTAMP
    //
    // Old version: acceptance sampling with up to 30 DOW attempts
    // + up to 50 HOD attempts = up to 80 iterations per timestamp.
    // At tens of thousands of orders this is a meaningful PHP cost.
    //
    // New version: array_rand() on pre-built flat weighted tables.
    // DOW table: each dow appears (weight × 10) times.
    // HOD table: each hour appears (weight × 10) times.
    // One array_rand() call each — O(1), no loops.
    //
    // Then we find the nearest matching calendar date within the
    // month slice whose day-of-week matches the sampled DOW.
    // ────────────────────────────────────────────────────────
    private function realisticTimestamp(int $monthIdx): string
    {
        $sliceStart = $this->historyStart + ($monthIdx * $this->sliceSize);
        $sliceEnd   = min(time(), $sliceStart + $this->sliceSize);

        // Pick a target DOW from the weighted table
        $targetDow = $this->dowPickTable[array_rand($this->dowPickTable)];

        // Find a day in the slice matching that DOW
        // Walk forward from a random point in the slice (max 7 steps)
        $base   = fake()->numberBetween($sliceStart, $sliceEnd);
        $baseDow = (int)date('w', $base);
        $offset  = ($targetDow - $baseDow + 7) % 7;
        $dayTs   = $base + ($offset * 86400);
        if ($dayTs > $sliceEnd) {
            $dayTs = $base - ((7 - $offset) * 86400);
        }
        if ($dayTs < $sliceStart) {
            $dayTs = $base; // fallback to unweighted day
        }

        // Pick hour from the weighted table — single array_rand()
        $hour   = $this->hodPickTable[array_rand($this->hodPickTable)];
        $minute = fake()->numberBetween(0, 59);
        $second = fake()->numberBetween(0, 59);

        return date('Y-m-d', $dayTs) . sprintf(' %02d:%02d:%02d', $hour, $minute, $second);
    }

    // ────────────────────────────────────────────────────────
    // EMAIL HELPERS
    // ────────────────────────────────────────────────────────

    /**
     * Normalize a company name into a valid email domain.
     * "Acme & Co."          → acme.co.com
     * "O'Brien LLC"         → obrien.llc.com
     * "Tech-Solutions, Inc" → tech.solutions.inc.com
     */
    private function normalizeDomain(string $companyName): string
    {
        $d = strtolower($companyName);
        $d = preg_replace("/['\x{2019}]s\b/u", '', $d);   // strip possessives
        $d = preg_replace('/[^a-z0-9]+/', '.', $d);         // non-alnum → dot
        $d = trim($d, '.');
        $d = preg_replace('/\.{2,}/', '.', $d);
        return ($d ?: 'tenant') . '.com';
    }

    /**
     * Derive a unique username from a full name.
     * "John Smith"   → john.smith
     * "Mary O'Brien" → mary.obrien
     * "José García"  → jose.garcia
     * Collisions → john.smith.2, john.smith.3 …
     */
    private function normalizeUsername(string $fullName, array $used): string
    {
        $parts     = preg_split('/\s+/', trim($fullName));
        $firstName = $parts[0] ?? 'user';
        $lastName  = end($parts) ?: 'user';

        $norm = function (string $part): string {
            if (function_exists('iconv')) {
                $part = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $part) ?: $part;
            }
            $part = strtolower($part);
            $part = preg_replace("/['\x{2019}]/u", '', $part);
            $part = preg_replace('/[^a-z0-9]+/', '', $part);
            return $part ?: 'x';
        };

        $base = trim(preg_replace('/\.{2,}/', '.', $norm($firstName) . '.' . $norm($lastName)), '.');
        $base = $base ?: 'user';

        $candidate = $base;
        $suffix    = 2;
        while (isset($used[$candidate])) {
            $candidate = $base . '.' . $suffix++;
        }
        return $candidate;
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