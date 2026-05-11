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
    use WithoutModelEvents; // disables all model observers — critical for stock/availability observer

    // ── Scale constants ─────────────────────────────────────
    private const TENANTS             = 300;
    private const USERS_PER_TENANT    = 50;
    private const ORDERS_MIN_PER_USER = 10;
    private const ORDERS_MAX_PER_USER = 25;
    private const MESSAGES_PER_TENANT = 40;
    private const MOVEMENTS_PER_ITEM  = 3;  // 1 initial + 2 extra
    private const CHUNK               = 500; // max rows per bulk insert

    private string $hashedPassword;

    // ────────────────────────────────────────────────────────
    // ENTRY POINT
    // ────────────────────────────────────────────────────────
    public function run(): void
    {
        // Hash once — reused for every user, saves ~750 bcrypt calls
        $this->hashedPassword = Hash::make('password');

        $catalog   = MenuItemFactory::catalog();
        $templates = MessageFactory::templates();

        $this->command->info('Seeding roles...');
        $this->seedRoles();

        for ($i = 1; $i <= self::TENANTS; $i++) {
            $this->command->info("Seeding tenant {$i} / " . self::TENANTS . '...');
            DB::transaction(fn() => $this->seedTenant($catalog, $templates));
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
    // TENANT — orchestrates all child seeders
    // ────────────────────────────────────────────────────────
    private function seedTenant(array $catalog, array $templates): void
    {
        $tenantId = DB::table('tenants')->insertGetId([
            'name'                 => fake()->company(),
            'subscription_active'  => fake()->boolean(90),
            'subscription_ends_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        [$adminIds, $staffIds, $userIds] = $this->seedUsers($tenantId);

        $menuItems = $this->seedCatalog($tenantId, $adminIds[0], $catalog);

        $this->seedOrders($userIds, $staffIds, $menuItems);

        $this->seedMessages($tenantId, array_merge($adminIds, $staffIds), $templates);
    }

    // ────────────────────────────────────────────────────────
    // USERS
    // Strategy: bulk insert all 50 → query back grouped by role
    // Returns: [adminIds[], staffIds[], userIds[]]
    // ────────────────────────────────────────────────────────
    private function seedUsers(int $tenantId): array
    {
        $total      = self::USERS_PER_TENANT;
        $adminCount = max(1, (int) round($total * 0.02)); // 1
        $staffCount = max(1, (int) round($total * 0.08)); // 4
        $userCount  = $total - $adminCount - $staffCount;  // 45

        $records = [];

        foreach (['admin' => $adminCount, 'kitchen_staff' => $staffCount, 'user' => $userCount] as $role => $count) {
            for ($i = 0; $i < $count; $i++) {
                $ts = fake()->dateTimeBetween('-6 months', 'now');
                $records[] = [
                    'tenant_id'  => $tenantId,
                    'name'       => fake()->name(),
                    'email'      => fake()->unique()->safeEmail(),
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

        // Query back — only one extra SELECT per tenant
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
    // CATALOG — categories + menu items + stock + movements
    //
    // categories:    serial insertGetId (catalog map drives this)
    // menu_items:    bulk per category → query back ids + prices
    // stock:         bulk all at once  → query back keyed by menu_item_id
    // movements:     pure bulk insert
    //
    // Returns: [{id, price}] for use in order seeding
    // ────────────────────────────────────────────────────────
    private function seedCatalog(int $tenantId, int $adminId, array $catalog): array
    {
        $allMenuItems = []; // [{id, price}] — returned to caller
        $stockMeta    = []; // [{menu_item_id, qty, threshold, ts}] — for building stock records + movements

        // ── Categories + Menu Items ──────────────────────────
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
                $itemTs = fake()->dateTimeBetween('-6 months', '-4 months');
                $itemRecords[] = [
                    'category_id'                => $categoryId,
                    'name'                       => $item['name'],
                    'description'                => $item['description'],
                    'image_path'                 => null,
                    'price'                      => fake()->randomFloat(2, 30, 450),
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

            // Query back this category's items — one SELECT per category
            $inserted = DB::table('menu_items')
                ->where('category_id', $categoryId)
                ->select('id', 'price', 'created_at')
                ->get();

            foreach ($inserted as $row) {
                $allMenuItems[] = ['id' => $row->id, 'price' => (float) $row->price];
                $stockMeta[]    = [
                    'menu_item_id' => $row->id,
                    'qty'          => fake()->numberBetween(0, 150),
                    'threshold'    => fake()->numberBetween(5, 20),
                    'ts'           => $row->created_at,
                ];
            }
        }

        // ── Stock — bulk insert all at once ──────────────────
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

        // Query back stock IDs keyed by menu_item_id — one SELECT total
        $menuItemIds = array_column($stockMeta, 'menu_item_id');
        $stockIdMap  = DB::table('stock')
            ->whereIn('menu_item_id', $menuItemIds)
            ->pluck('id', 'menu_item_id'); // [menu_item_id => stock_id]

        // ── Stock Movements — pure bulk insert ───────────────
        $movementRecords = [];

        foreach ($stockMeta as $m) {
            $stockId = $stockIdMap[$m['menu_item_id']];
            $prev    = $m['qty'];

            // Movement 1: initial load
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

            // Movements 2–N: random operational movements over time
            for ($x = 1; $x < self::MOVEMENTS_PER_ITEM; $x++) {
                $type    = fake()->randomElement(['restock', 'sale', 'adjustment', 'damage']);
                $changed = match ($type) {
                    'sale', 'damage' => -fake()->numberBetween(1, max(1, min($prev, 10))),
                    'restock'        => fake()->numberBetween(10, 50),
                    'adjustment'     => fake()->numberBetween(-5, 10),
                };
                $new  = max(0, $prev + $changed);
                $movTs = fake()->dateTimeBetween('-4 months', 'now');

                $movementRecords[] = [
                    'stock_id'          => $stockId,
                    'movement_type'     => $type,
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
    // ORDERS + ORDER ITEMS + TRANSACTIONS
    //
    // 1. Bulk insert all orders (total_amount = 0 placeholder)
    // 2. Query back order ids + statuses
    // 3. Build order_items for each order → bulk insert
    // 4. Single CASE WHEN SQL to update ALL totals at once
    // 5. Build transactions for served orders → bulk insert
    // ────────────────────────────────────────────────────────
    private function seedOrders(array $userIds, array $staffIds, array $menuItems): void
    {
        // ── Step 1: Build + bulk insert all orders ───────────
        $orderRows = [];

        foreach ($userIds as $userId) {
            $count = fake()->numberBetween(self::ORDERS_MIN_PER_USER, self::ORDERS_MAX_PER_USER);
            for ($o = 0; $o < $count; $o++) {
                $status = fake()->randomElement([
                    'served', 'served', 'served', 'served',
                    'pending', 'preparing', 'ready', 'canceled',
                ]);
                $ts = fake()->dateTimeBetween('-6 months', 'now');
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

        foreach (array_chunk($orderRows, self::CHUNK) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        // ── Step 2: Query back inserted orders ───────────────
        // Scoped to this tenant's users + total_amount = 0
        // so we only touch what we just inserted
        $orders = DB::table('orders')
            ->whereIn('user_id', $userIds)
            ->where('total_amount', 0)
            ->select('id', 'status', 'created_at')
            ->get();

        // ── Step 3: Build order_items ─────────────────────────
        $orderItemRows   = [];
        $orderTotals     = []; // [order_id => total_amount]
        $transactionRows = [];

        $menuCount = count($menuItems);

        foreach ($orders as $order) {
            $pickCount = min(fake()->numberBetween(1, 3), $menuCount);
            $picked    = fake()->randomElements($menuItems, $pickCount);
            $total     = 0;

            foreach ($picked as $item) {
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

            // ── Transactions for served orders only ───────────
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

        // ── Step 5: Update ALL totals in a single SQL call ───
        // CASE WHEN id = 1 THEN 450.00 WHEN id = 2 THEN 120.50 ... END
        // One UPDATE statement regardless of how many orders
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
    // MESSAGES — pure bulk insert
    // Senders and receivers are always from admin + staff pool
    // (internal comms only — users don't send messages)
    // ────────────────────────────────────────────────────────
    private function seedMessages(int $tenantId, array $staffAndAdminIds, array $templates): void
    {
        $tagKeys = array_keys($templates);
        $records = [];

        for ($m = 0; $m < self::MESSAGES_PER_TENANT; $m++) {
            $tag      = fake()->randomElement($tagKeys);
            $template = fake()->randomElement($templates[$tag]);
            $senderId = fake()->randomElement($staffAndAdminIds);

            // Receiver must be a different person
            $pool       = array_values(array_filter($staffAndAdminIds, fn($id) => $id !== $senderId));
            $receiverId = fake()->randomElement(empty($pool) ? $staffAndAdminIds : $pool);

            $ts = fake()->dateTimeBetween('-6 months', 'now');

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
}