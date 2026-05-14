<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AiController extends Controller
{
    /**
     * Get affinity items for a specific menu item based on the last 14 days.
     */
    public function getAffinity(Request $request, MenuItem $menuItem): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $this->authorizeItemTenant($menuItem, $tenantId);

        $days = 14;
        $startDate = now()->subDays($days);

        // 1. Get all orders (IDs) that contain this anchor item in the last 14 days
        $orderIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.menu_item_id', $menuItem->id)
            ->where('orders.created_at', '>=', $startDate)
            ->pluck('order_items.order_id');

        $totalAnchorOrders = $orderIds->count();

        if ($totalAnchorOrders === 0) {
            return response()->json([
                'anchor' => [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                ],
                'companions' => [],
                'message' => "No sales recorded for this item in the last $days days."
            ]);
        }

        // 2. Find other items in those same orders
        $companions = OrderItem::query()
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->where('order_items.menu_item_id', '!=', $menuItem->id) // Exclude the anchor item itself
            ->select(
                'menu_items.id',
                'menu_items.name',
                DB::raw('COUNT(DISTINCT order_items.order_id) as common_orders')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('common_orders')
            ->take(5)
            ->get();

        // 3. Map to result with percentage
        $predictions = $companions->map(function ($item) use ($totalAnchorOrders) {
            $rate = ($item->common_orders / $totalAnchorOrders) * 100;
            return [
                'id' => $item->id,
                'name' => $item->name,
                'rate' => round($rate, 1),
                'count' => $item->common_orders,
            ];
        });

        return response()->json([
            'anchor' => [
                'id' => $menuItem->id,
                'name' => $menuItem->name,
            ],
            'period_days' => $days,
            'total_anchor_orders' => $totalAnchorOrders,
            'predictions' => $predictions,
        ]);
    }

    /**
     * Search for items to use in the affinity tool.
     */
    public function searchItems(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $query = $request->query('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([]);
        }

        $items = MenuItem::query()
            ->whereHas('category', fn($q) => $q->where('tenant_id', $tenantId))
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name')
            ->take(10)
            ->get();

        return response()->json($items);
    }

    private function authorizeItemTenant(MenuItem $menuItem, int $tenantId): void
    {
        if ($menuItem->category->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized.');
        }
    }
}
