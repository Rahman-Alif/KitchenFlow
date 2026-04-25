<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\PlaceOrderRequest;
use App\Http\Resources\User\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\User\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['orderItems.menuItem:id,name'])
            ->latest()
            ->get();

        return OrderResource::collection($orders)
            ->response()
            ->setStatusCode(200);
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->place(
            $request->user()->id,
            $request->user()->tenant_id,
            $request->validated('items'),
            $request->validated('notes'),
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }

        $order->load(['orderItems.menuItem:id,name']);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(200);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }

        if ($order->status !== 'pending') {
            abort(422, 'Only pending orders can be edited.');
        }

        $request->validate([
            'items'              => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity'   => ['required', 'integer', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $order) {
            foreach ($request->items as $item) {
                $orderItem = OrderItem::where('id', $item['order_item_id'])
                    ->where('order_id', $order->id)
                    ->firstOrFail();

                $diff = $item['quantity'] - $orderItem->quantity;

                if ($item['quantity'] === 0) {
                    // Restore stock and remove item
                    $orderItem->menuItem->increment('stock_quantity', $orderItem->quantity);
                    // Re-enable if it was auto-disabled
                    if (!$orderItem->menuItem->is_available && $orderItem->menuItem->stock_quantity > 0) {
                        $orderItem->menuItem->update(['is_available' => true]);
                    }
                    $orderItem->delete();
                } elseif ($diff > 0) {
                    // Increasing quantity — check stock
                    if ($orderItem->menuItem->stock_quantity < $diff) {
                        abort(422, "Insufficient stock for '{$orderItem->menuItem->name}'. Available: {$orderItem->menuItem->stock_quantity}.");
                    }
                    $orderItem->menuItem->decrement('stock_quantity', $diff);
                    if ($orderItem->menuItem->stock_quantity === 0) {
                        $orderItem->menuItem->update(['is_available' => false]);
                    }
                    $orderItem->update(['quantity' => $item['quantity']]);
                } elseif ($diff < 0) {
                    // Decreasing quantity — restore stock
                    $orderItem->menuItem->increment('stock_quantity', abs($diff));
                    $orderItem->update(['quantity' => $item['quantity']]);
                }
            }

            // Recalculate total
            $order->load('orderItems');

            if ($order->orderItems->isEmpty()) {
                abort(422, 'Order must have at least one item.');
            }

            $total = $order->orderItems->sum(fn ($i) => $i->quantity * $i->unit_price);
            $order->update([
                'total_amount' => $total,
                'notes'        => $request->notes ?? $order->notes,
            ]);
        });

        $order->load(['orderItems.menuItem:id,name']);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(200);
    }


public function cancel(Request $request, Order $order): JsonResponse
{
    if ($order->user_id !== $request->user()->id) {
        abort(403, 'You do not have access to this order.');
    }

    if ($order->status !== 'pending') {
        abort(422, 'Only pending orders can be canceled.');
    }

    $order->load('orderItems.menuItem');

    DB::transaction(function () use ($order) {
        foreach ($order->orderItems as $item) {
            $item->menuItem->increment('stock_quantity', $item->quantity);
            if (!$item->menuItem->is_available && $item->menuItem->fresh()->stock_quantity > 0) {
                $item->menuItem->update(['is_available' => true]);
            }
        }
        $order->update(['status' => 'canceled']);
    });

    $order->load(['orderItems.menuItem:id,name']);

    return (new OrderResource($order))
        ->response()
        ->setStatusCode(200);
}

}