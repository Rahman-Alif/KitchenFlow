<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        $user = User::with('tenant')
            ->where('email', $email)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        if (!$user->tenant->subscription_active) {
            throw ValidationException::withMessages([
                'email' => ['Your organization subscription is inactive.'],
            ]);
        }

        if ($user->tenant->subscription_ends_at && $user->tenant->subscription_ends_at->isPast()) {
            throw ValidationException::withMessages([
                'email' => ['Your organization subscription has expired.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }
    public function requestPasswordReset(string $email): void
    {
        $user = User::where('email', $email)->firstOrFail();

        // Delete any existing token for this user
        \App\Models\PasswordResetToken::where('user_id', $user->id)->delete();

        $token = \Illuminate\Support\Str::random(64);

        \App\Models\PasswordResetToken::create([
            'user_id'    => $user->id,
            'token'      => $token,
            'expires_at' => now()->addMinutes(60),
        ]);

        \Illuminate\Support\Facades\Mail::to($user->email)->send(
            new \App\Mail\PasswordResetMail($token)
        );
    }

    public function confirmPasswordReset(string $token, string $password): void
    {
        $record = \App\Models\PasswordResetToken::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $record->user->update(['password' => $password]);

        $record->delete();
    }
}