<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $date = $request->query('date')
            ? now()->parse($request->query('date'))->toDateString()
            : now()->toDateString();

        // Orders scoped to tenant for the given date
        $orders = Order::whereHas('user', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('created_at', $date)
            ->get();

        // Revenue from served orders only
        $revenue = Transaction::whereHas('order.user', fn($q) => $q->where('tenant_id', $tenantId))
            ->whereDate('created_at', $date)
            ->sum('tendered_amount');

        // Order counts by status
        $statusCounts = $orders->groupBy('status')->map->count();
        $ordersByStatus = $statusCounts
            ->map(fn ($count, $status) => [
                'status' => $status,
                'count' => $count,
            ])
            ->values();

        // Top selling items
        $topItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->join('categories', 'categories.id', '=', 'menu_items.category_id')
            ->where('categories.tenant_id', $tenantId)
            ->whereDate('orders.created_at', $date)
            ->selectRaw('menu_items.id as id, menu_items.name as name, SUM(order_items.quantity) as total_sold')
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'id' => (int) $item->id,
                'name' => $item->name,
                'quantity' => (int) ($item->total_sold ?? 0),
                'total_sold' => (int) ($item->total_sold ?? 0),
            ]);

        return response()->json([
            'date'         => $date,
            'total_orders' => $orders->count(),
            'total_revenue'=> $revenue,
            'orders_by_status' => $ordersByStatus,
            'revenue'      => $revenue,
            'order_status' => $statusCounts,
            'top_items'    => $topItems,
        ]);
    }
}