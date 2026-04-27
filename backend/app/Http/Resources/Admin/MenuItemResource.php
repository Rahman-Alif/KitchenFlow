<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'category'            => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ],
            'name'                => $this->name,
            'description'         => $this->description,
            'image_url'           => $this->image_path 
                ? (filter_var($this->image_path, FILTER_VALIDATE_URL) ? $this->image_path : asset('storage/' . $this->image_path)) 
                : null,
            'price'               => $this->price,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_available'               => $this->is_available,
            'needs_restock'              => $this->needs_restock,
            'requested_restock_quantity' => $this->requested_restock_quantity,
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
            'created_by'                 => $this->created_by,
            'created_by_name'            => $this->createdBy?->name,
            'updated_by'                 => $this->updated_by,
            'updated_by_name'            => $this->updatedBy?->name,
        ];
    }
}