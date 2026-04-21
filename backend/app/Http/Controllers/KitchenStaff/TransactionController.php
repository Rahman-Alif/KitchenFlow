<?php

namespace App\Http\Controllers\KitchenStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\KitchenStaff\RecordTransactionRequest;
use App\Http\Resources\KitchenStaff\TransactionResource;
use App\Services\KitchenStaff\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {}

    public function store(RecordTransactionRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $staffId  = $request->user()->id;

        $transaction = $this->transactionService->record(
            $id,
            (float) $request->validated('tendered_amount'),
            $tenantId,
            $staffId
        );

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }
}