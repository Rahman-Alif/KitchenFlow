<?php

namespace Tests\Feature;

use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * TC01 – TC06 | Authentication
 *
 * Covers login success, login failures (wrong password, deactivated account,
 * inactive/expired tenant subscription), logout, and password reset request.
 */
class AuthTest extends TestCase
{
    // ─── TC01 ───────────────────────────────────────────────────────────────

    /**
     * TC01: Successful login returns a Bearer token and user data.
     * All three roles use the same endpoint so we test admin as the
     * representative case. Separate role-level tests are in RoleBoundaryTest.
     */
    public function test_successful_login_returns_token_and_user_data(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'sarah.ahmed@nexuscorp.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'role'],
                ],
            ])
            ->assertJsonPath('data.user.email', 'sarah.ahmed@nexuscorp.com')
            ->assertJsonPath('data.user.role', 'admin');
    }

    // ─── TC02 ───────────────────────────────────────────────────────────────

    /**
     * TC02: Login with an incorrect password returns 422 with a field error.
     */
    public function test_login_with_wrong_password_returns_422(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'sarah.ahmed@nexuscorp.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    // ─── TC03 ───────────────────────────────────────────────────────────────

    /**
     * TC03: A deactivated user (is_active = false) cannot log in.
     * Seeder creates rafiq.islam as is_active = false.
     */
    public function test_deactivated_user_cannot_login(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'rafiq.islam@nexuscorp.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    // ─── TC04 ───────────────────────────────────────────────────────────────

    /**
     * TC04: A user whose tenant subscription has lapsed cannot log in.
     * Orion Labs is seeded with subscription_active = false and
     * subscription_ends_at in the past.
     */
    public function test_user_from_inactive_tenant_cannot_login(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'david.park@orionlabs.io',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrorFor('email');
    }

    // ─── TC05 ───────────────────────────────────────────────────────────────

    /**
     * TC05: Logout invalidates the Sanctum token.
     * A subsequent request with the same token must be rejected with 401.
     */
    public function test_logout_invalidates_token(): void
    {
        $user = $this->admin();

        // Obtain a real token via login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('data.token');

        // Logout using that token
        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        // Same token must now be rejected
        $this->withToken($token)
            ->getJson('/api/admin/users')
            ->assertStatus(401);
    }

    // ─── TC06 ───────────────────────────────────────────────────────────────

    /**
     * TC06: Password reset request creates a DB token and sends an email.
     * Uses Mail::fake() so no real SMTP call is made.
     * Nusrat is seeded with a valid (non-expired) password reset token already,
     * but the endpoint deletes the old one and creates a fresh one.
     */
    public function test_password_reset_request_queues_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/password-reset/request', [
            'email' => 'tanvir.mahmud@nexuscorp.com',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $this->customer()->id,
        ]);

        Mail::assertSent(\App\Mail\PasswordResetMail::class);
    }
}
