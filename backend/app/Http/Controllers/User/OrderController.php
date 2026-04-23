<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\PlaceOrderRequest;
use App\Http\Resources\User\OrderResource;
use App\Models\Order;
use App\Services\User\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

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
        // Ensure the order belongs to the authenticated user
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'You do not have access to this order.');
        }

        $order->load(['orderItems.menuItem:id,name']);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(200);
    }
}

