<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\PlaceOrderRequest;
use App\Http\Resources\User\OrderResource;
use App\Services\User\OrderService;
use Illuminate\Http\JsonResponse;

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
}