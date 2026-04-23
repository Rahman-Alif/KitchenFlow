<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today()->setTime(12, 0, 0);
        $fewDaysBack = $today->subDays(4)->setTime(12, 0, 0);

        // --------------------
        // Today's order set
        // --------------------
        $this->createOrderWithItems(
            userId: 3,
            status: 'served',
            notes: null,
            orderedAt: $today->setTime(12, 15),
            items: [
                ['menu_item_id' => 1, 'quantity' => 1, 'unit_price' => 120],
                ['menu_item_id' => 4, 'quantity' => 1, 'unit_price' => 60],
                ['menu_item_id' => 6, 'quantity' => 1, 'unit_price' => 35],
            ],
            transaction: ['recorded_by' => 2, 'tendered_amount' => 220, 'change_returned' => 5]
        );

        $this->createOrderWithItems(
            userId: 4,
            status: 'pending',
            notes: 'Less spicy please',
            orderedAt: $today->setTime(13, 10),
            items: [
                ['menu_item_id' => 2, 'quantity' => 1, 'unit_price' => 150],
                ['menu_item_id' => 9, 'quantity' => 1, 'unit_price' => 20],
                ['menu_item_id' => 10, 'quantity' => 1, 'unit_price' => 15],
            ]
        );

        $this->createOrderWithItems(
            userId: 3,
            status: 'preparing',
            notes: null,
            orderedAt: $today->setTime(14, 5),
            items: [
                ['menu_item_id' => 1, 'quantity' => 1, 'unit_price' => 120],
                ['menu_item_id' => 8, 'quantity' => 1, 'unit_price' => 45],
            ]
        );

        $this->createOrderWithItems(
            userId: 4,
            status: 'ready',
            notes: null,
            orderedAt: $today->setTime(15, 20),
            items: [
                ['menu_item_id' => 12, 'quantity' => 1, 'unit_price' => 200],
                ['menu_item_id' => 6, 'quantity' => 1, 'unit_price' => 35],
            ]
        );

        // -------------------------
        // Few-days-back order set
        // -------------------------
        $this->createOrderWithItems(
            userId: 3,
            status: 'served',
            notes: 'Office lunch order',
            orderedAt: $fewDaysBack->setTime(11, 40),
            items: [
                ['menu_item_id' => 3, 'quantity' => 2, 'unit_price' => 80],
                ['menu_item_id' => 8, 'quantity' => 2, 'unit_price' => 45],
            ],
            transaction: ['recorded_by' => 2, 'tendered_amount' => 260, 'change_returned' => 10]
        );

        $this->createOrderWithItems(
            userId: 4,
            status: 'served',
            notes: null,
            orderedAt: $fewDaysBack->setTime(12, 30),
            items: [
                ['menu_item_id' => 12, 'quantity' => 1, 'unit_price' => 200],
            ],
            transaction: ['recorded_by' => 2, 'tendered_amount' => 200, 'change_returned' => 0]
        );

        $this->createOrderWithItems(
            userId: 3,
            status: 'canceled',
            notes: 'Customer requested cancellation',
            orderedAt: $fewDaysBack->setTime(13, 55),
            items: [
                ['menu_item_id' => 5, 'quantity' => 2, 'unit_price' => 40],
            ]
        );

        $this->createOrderWithItems(
            userId: 4,
            status: 'ready',
            notes: null,
            orderedAt: $fewDaysBack->setTime(14, 45),
            items: [
                ['menu_item_id' => 2, 'quantity' => 1, 'unit_price' => 150],
                ['menu_item_id' => 9, 'quantity' => 2, 'unit_price' => 20],
            ]
        );
    }

    /**
     * @param  array<int, array{menu_item_id:int, quantity:int, unit_price:int|float}>  $items
     * @param  array{recorded_by:int, tendered_amount:int|float, change_returned:int|float}|null  $transaction
     */
    private function createOrderWithItems(
        int $userId,
        string $status,
        ?string $notes,
        CarbonImmutable $orderedAt,
        array $items,
        ?array $transaction = null
    ): void {
        $totalAmount = collect($items)->sum(
            fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']
        );

        $order = new Order([
            'user_id' => $userId,
            'status' => $status,
            'total_amount' => $totalAmount,
            'notes' => $notes,
        ]);
        $order->created_at = $orderedAt;
        $order->updated_at = $orderedAt;
        $order->save();

        foreach ($items as $item) {
            $orderItem = new OrderItem([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
            $orderItem->created_at = $orderedAt;
            $orderItem->updated_at = $orderedAt;
            $orderItem->save();
        }

        if ($transaction !== null) {
            $paymentTime = $orderedAt->addMinutes(18);
            $orderTransaction = new Transaction([
                'order_id' => $order->id,
                'recorded_by' => $transaction['recorded_by'],
                'tendered_amount' => $transaction['tendered_amount'],
                'change_returned' => $transaction['change_returned'],
            ]);
            $orderTransaction->created_at = $paymentTime;
            $orderTransaction->updated_at = $paymentTime;
            $orderTransaction->save();
        }
    }
}