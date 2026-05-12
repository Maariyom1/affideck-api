<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_and_me_flow(): void
    {
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'referrer_code' => 'REF-001',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'roles', 'plan', 'balance', 'level', 'streak', 'unreadCounts', 'permissions', 'featureFlags'],
            ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'ada@example.com',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user',
            ]);

        $accessToken = $loginResponse->json('access_token');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonPath('data.plan', 'free');
    }

    public function test_admin_login_and_me_return_admin_roles(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@affideck.com',
            'password' => 'admin',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@affideck.com',
            'password' => 'admin',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user.roles.0', 'admin');

        $accessToken = $loginResponse->json('access_token');

        $meResponse = $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->getJson('/api/me');

        $meResponse
            ->assertOk()
            ->assertJsonPath('data.roles.0', 'admin');
    }

    public function test_refresh_rotates_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'grace@example.com',
        ]);

        $tokens = ApiToken::issuePair($user);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user',
            ]);
    }

    public function test_authenticated_notification_endpoints_work(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/notifications');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page', 'unread_count'],
            ]);
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $user = User::factory()->create();
        $tokens = ApiToken::issuePair($user);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->postJson('/api/logout');

        $logoutResponse->assertNoContent();

        $this->withHeader('Authorization', 'Bearer '.$tokens['access_token'])
            ->getJson('/api/me')
            ->assertUnauthorized();
    }
}