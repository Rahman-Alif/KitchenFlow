<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'total_amount' => number_format($this->total_amount, 2, '.', ''),
            'notes'        => $this->notes,
            'created_at'   => $this->created_at->toDateTimeString(),

            'placed_by' => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ],

            'items' => $this->orderItems->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->menuItem?->name ?? 'Deleted item',
                'quantity'   => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2, '.', ''),
                'subtotal'   => number_format($item->quantity * $item->unit_price, 2, '.', ''),
            ]),

            'transaction' => $this->whenLoaded('transaction', function () {
                if (!$this->transaction) return null;
                return [
                    'tendered_amount' => number_format($this->transaction->tendered_amount, 2, '.', ''),
                    'change_returned' => number_format($this->transaction->change_returned, 2, '.', ''),
                    'recorded_by'     => $this->transaction->recordedBy?->name ?? '—',
                    'recorded_at'     => $this->transaction->created_at->toDateTimeString(),
                ];
            }),
        ];
    }
}