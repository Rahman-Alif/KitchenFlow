<?php

namespace App\Http\Controllers\KitchenStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\KitchenStaff\UpdateOrderStatusRequest;
use App\Http\Resources\KitchenStaff\OrderQueueResource;
use App\Models\Order;
use App\Services\KitchenStaff\OrderQueueService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderQueueController extends Controller
{
    public function __construct(
        private readonly OrderQueueService $orderQueueService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $orders = $this->orderQueueService->getActiveQueue($tenantId);

        return OrderQueueResource::collection($orders);
    }

    public function show(Request $request, int $id): OrderQueueResource
    {
        $tenantId = $request->user()->tenant_id;

        $order = $this->orderQueueService->getOrderDetail($id, $tenantId);

        return new OrderQueueResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): OrderQueueResource
    {
        $order = Order::with(['user', 'orderItems.menuItem'])->findOrFail($id);

        $tenantId = $request->user()->tenant_id;

        $order = $this->orderQueueService->updateStatus($order, $request->validated('status'), $tenantId);

        return new OrderQueueResource($order);
    }
}