<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    private string $token;

    public function __construct($resource, string $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    public function toArray($request): array
    {
        return [
            'token' => $this->token,
            'user'  => [
                'id'    => $this->id,
                'name'  => $this->name,
                'email' => $this->email,
                'role'  => $this->role,
                'tenant_id' => $this->tenant_id,  // ← added
            ],
        ];
    }
}