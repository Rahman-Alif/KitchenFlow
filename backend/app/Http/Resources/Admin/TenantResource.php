<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'                  => $this->name,
            'subscription_active'   => $this->subscription_active,
            'subscription_ends_at'  => $this->subscription_ends_at?->toDateString(),
        ];
    }
}