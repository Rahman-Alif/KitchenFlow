<?php

namespace App\Services\KitchenStaff;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderQueueService
{
    private const VALID_TRANSITIONS = [
        'pending'   => ['preparing', 'canceled'],
        'preparing' => ['ready'],
        'ready'     => ['served'],
    ];

    /**
     * Returns active orders (pending, preparing, ready) for the given tenant,
     * oldest first. Eager loads to prevent N+1.
     */
    public function getActiveQueue(int $tenantId): Collection
    {
        return Order::whereHas('user', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->with([
                'user:id,name',
                'orderItems.menuItem:id,name',
            ])
            ->oldest()
            ->get();
    }

    /**
     * Advances an order status along the strictly linear pipeline.
     * Aborts with 403 on tenant mismatch, 422 on invalid transition.
     */
    public function updateStatus(Order $order, string $newStatus, int $tenantId): Order
    {
        if ($order->user->tenant_id !== $tenantId) {
            abort(403, 'Order does not belong to your organization.');
        }

        $currentStatus = $order->status;
        $allowed = self::VALID_TRANSITIONS[$currentStatus] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            abort(422, "Cannot transition order from '{$currentStatus}' to '{$newStatus}'.");
        }

        $order->status = $newStatus;
        $order->save();

        return $order->load(['user:id,name', 'orderItems.menuItem:id,name']);
    }

    /**
     * Returns a single order by ID, scoped to tenant.
     * Aborts 403 if order belongs to a different tenant.
     * Aborts 404 if order not found.
     */
    public function getOrderDetail(int $orderId, int $tenantId): Order
    {
        $order = Order::with([
            'user:id,name',
            'orderItems.menuItem:id,name',
            'transaction',
        ])->findOrFail($orderId);

        if ($order->user->tenant_id !== $tenantId) {
            abort(403, 'Order does not belong to your organization.');
        }

        return $order;
    }
}