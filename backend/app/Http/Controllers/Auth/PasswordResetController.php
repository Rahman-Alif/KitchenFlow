<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetRequestRequest;
use App\Http\Requests\Auth\PasswordResetConfirmRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function request(PasswordResetRequestRequest $request): JsonResponse
    {
        $this->authService->requestPasswordReset($request->email);

        return response()->json(['message' => 'Password reset token sent to your email.']);
    }

    public function confirm(PasswordResetConfirmRequest $request): JsonResponse
    {
        $this->authService->confirmPasswordReset(
            $request->token,
            $request->password
        );

        return response()->json(['message' => 'Password reset successful.']);
    }
}