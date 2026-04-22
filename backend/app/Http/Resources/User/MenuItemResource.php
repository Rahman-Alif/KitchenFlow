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
            'image_path'  => $this->image_path,
            'price'       => $this->price,
            'category'    => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ],
        ];
    }
}