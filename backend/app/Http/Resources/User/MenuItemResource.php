<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'image_path'  => $this->image_path 
                ? (filter_var($this->image_path, FILTER_VALIDATE_URL) ? $this->image_path : asset('storage/' . $this->image_path)) 
                : null,
            'price'       => $this->price,
            'category'    => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ],
        ];
    }
}