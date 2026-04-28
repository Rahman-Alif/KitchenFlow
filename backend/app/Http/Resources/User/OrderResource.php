<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'total_amount' => $this->total_amount,
            'notes'        => $this->notes,
            'items'        => $this->orderItems->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->menuItem?->name ?? 'Deleted Item',
                'image_url'  => $item->menuItem?->image_path 
                    ? (filter_var($item->menuItem->image_path, FILTER_VALIDATE_URL) ? $item->menuItem->image_path : asset('storage/' . $item->menuItem->image_path)) 
                    : null,
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
            ]),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}