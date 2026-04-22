<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Order 1 — served with transaction (user: john, id 3)
        $order1 = Order::create([
            'user_id'      => 3,
            'status'       => 'served',
            'total_amount' => 195,
            'notes'        => null,
        ]);
        OrderItem::create(['order_id' => $order1->id, 'menu_item_id' => 1, 'quantity' => 1, 'unit_price' => 120]);
        OrderItem::create(['order_id' => $order1->id, 'menu_item_id' => 4, 'quantity' => 1, 'unit_price' => 60]);
        OrderItem::create(['order_id' => $order1->id, 'menu_item_id' => 6, 'quantity' => 1, 'unit_price' => 35]);
        Transaction::create([
            'order_id'        => $order1->id,
            'recorded_by'     => 2,
            'tendered_amount' => 200,
            'change_returned' => 5,
        ]);

        // Order 2 — pending (user: jane, id 4)
        $order2 = Order::create([
            'user_id'      => 4,
            'status'       => 'pending',
            'total_amount' => 160,
            'notes'        => 'Less spicy please',
        ]);
        OrderItem::create(['order_id' => $order2->id, 'menu_item_id' => 2, 'quantity' => 1, 'unit_price' => 150]);
        OrderItem::create(['order_id' => $order2->id, 'menu_item_id' => 9, 'quantity' => 1, 'unit_price' => 20]);
        OrderItem::create(['order_id' => $order2->id, 'menu_item_id' => 10,'quantity' => 1, 'unit_price' => 15]);

        // Order 3 — preparing (user: john, id 3)
        $order3 = Order::create([
            'user_id'      => 3,
            'status'       => 'preparing',
            'total_amount' => 120,
            'notes'        => null,
        ]);
        OrderItem::create(['order_id' => $order3->id, 'menu_item_id' => 1, 'quantity' => 1, 'unit_price' => 120]);

        // Order 4 — ready (user: jane, id 4)
        $order4 = Order::create([
            'user_id'      => 4,
            'status'       => 'ready',
            'total_amount' => 245,
            'notes'        => null,
        ]);
        OrderItem::create(['order_id' => $order4->id, 'menu_item_id' => 12,'quantity' => 1, 'unit_price' => 200]);
        OrderItem::create(['order_id' => $order4->id, 'menu_item_id' => 8, 'quantity' => 1, 'unit_price' => 45]);

        // Order 5 — canceled (user: john, id 3)
        $order5 = Order::create([
            'user_id'      => 3,
            'status'       => 'canceled',
            'total_amount' => 80,
            'notes'        => null,
        ]);
        OrderItem::create(['order_id' => $order5->id, 'menu_item_id' => 3, 'quantity' => 1, 'unit_price' => 80]);

        // Order 6 — served with transaction (user: jane, id 4)
        $order6 = Order::create([
            'user_id'      => 4,
            'status'       => 'served',
            'total_amount' => 200,
            'notes'        => null,
        ]);
        OrderItem::create(['order_id' => $order6->id, 'menu_item_id' => 12,'quantity' => 1, 'unit_price' => 200]);
        Transaction::create([
            'order_id'        => $order6->id,
            'recorded_by'     => 2,
            'tendered_amount' => 200,
            'change_returned' => 0,
        ]);
    }
}