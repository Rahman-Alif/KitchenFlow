<?php

namespace App\Http\Resources\KitchenStaff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_id'        => $this->order_id,
            'tendered_amount' => $this->tendered_amount,
            'change_returned' => $this->change_returned,
            'recorded_by'     => [
                'id'   => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ],
            'recorded_at' => $this->created_at->toISOString(),
        ];
    }
}