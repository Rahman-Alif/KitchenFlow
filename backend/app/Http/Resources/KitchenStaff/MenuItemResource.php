<?php

namespace App\Http\Resources\KitchenStaff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'description'         => $this->description,
            'image_path'          => $this->image_path,
            'category'            => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ],
            'price'               => $this->price,
            'stock_quantity'      => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_available'        => $this->is_available,
            'is_low_stock'        => $this->stock_quantity <= $this->low_stock_threshold,
        ];
    }
}
