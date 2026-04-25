<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $query = Order::with([
                'user',
                'orderItems.menuItem',
                'transaction.recordedBy',
            ])
            ->whereHas('user', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by user (employee) name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            });
        }

        // Search by menu item name
        if ($request->filled('item')) {
            $item = $request->item;
            $query->whereHas('orderItems.menuItem', function ($q) use ($item) {
                $q->where('name', 'ilike', "%{$item}%");
            });
        }

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $tenantId = $request->user()->tenant_id;

        // Ensure order belongs to this tenant
        abort_unless(
            $order->user->tenant_id === $tenantId,
            403,
            'Order does not belong to your organisation.'
        );

        $order->load([
            'user',
            'orderItems.menuItem',
            'transaction.recordedBy',
        ]);

        return new OrderResource($order);
    }
}