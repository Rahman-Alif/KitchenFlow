<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\MenuItem;
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

        // Top selling items
        $topItems = MenuItem::withTrashed()
            ->whereHas('category', fn($q) => $q->where('tenant_id', $tenantId))
            ->withSum(['orderItems as total_sold' => function ($q) use ($date) {
                $q->whereHas('order', fn($q) => $q->whereDate('created_at', $date));
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'id'         => $item->id,
                'name'       => $item->name,
                'total_sold' => $item->total_sold ?? 0,
            ]);

        return response()->json([
            'date'         => $date,
            'total_orders' => $orders->count(),
            'revenue'      => $revenue,
            'order_status' => $statusCounts,
            'top_items'    => $topItems,
        ]);
    }
}