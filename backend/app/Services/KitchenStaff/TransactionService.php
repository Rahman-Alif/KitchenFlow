<?php

namespace App\Services\KitchenStaff;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Records a cash transaction for an order and marks it as served.
     * Wrapped in a DB transaction — both writes succeed or neither does.
     *
     * Aborts:
     *   403 — order belongs to a different tenant
     *   422 — order is not in 'ready' status
     *   422 — transaction already exists for this order
     *   422 — tendered amount is less than order total
     */
    public function record(int $orderId, float $tenderedAmount, int $tenantId, int $staffId): Transaction
    {
        $order = Order::with('user')->findOrFail($orderId);

        if ($order->user->tenant_id !== $tenantId) {
            abort(403, 'Order does not belong to your organization.');
        }

        if ($order->status !== 'ready') {
            abort(422, 'Transaction can only be recorded for orders with status: ready.');
        }

        if (Transaction::where('order_id', $orderId)->exists()) {
            abort(422, 'A transaction has already been recorded for this order.');
        }

        if ($tenderedAmount < $order->total_amount) {
            abort(422, 'Tendered amount cannot be less than the order total of ' . $order->total_amount . '.');
        }

        return DB::transaction(function () use ($order, $tenderedAmount, $staffId) {
            $transaction = Transaction::create([
                'order_id'        => $order->id,
                'recorded_by'     => $staffId,
                'tendered_amount' => $tenderedAmount,
                'change_returned' => $tenderedAmount - $order->total_amount,
            ]);

            $order->status = 'served';
            $order->save();

            return $transaction->load('recordedBy:id,name');
        });
    }
}