<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Services\AuthService;

class LoginController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function store(LoginRequest $request): AuthResource
    {
        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        return new AuthResource($result['user'], $result['token']);
    }
}