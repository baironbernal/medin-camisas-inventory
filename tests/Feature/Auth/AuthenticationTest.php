<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ── Register ─────────────────────────────────────────────────────────────

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name'         => 'Juan Pérez',
            'email'        => 'juan@example.com',
            'phone_number' => '3001234567',
            'password'     => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'user'       => ['email' => 'juan@example.com'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
    }

    public function test_register_returns_a_usable_bearer_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'         => 'Test User',
            'email'        => 'test@example.com',
            'phone_number' => '3009999999',
            'password'     => 'securepass',
        ]);

        $token = $response->json('access_token');

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name'         => 'Another User',
            'email'        => 'existing@example.com',
            'phone_number' => '3000000000',
            'password'     => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_fails_without_required_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email'        => 'test@example.com',
            'phone_number' => '3000000000',
            'password'     => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_register_fails_without_required_phone_number(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('phone_number');
    }

    public function test_register_fails_with_password_shorter_than_8_chars(): void
    {
        $response = $this->postJson('/api/register', [
            'name'         => 'Test User',
            'email'        => 'test@example.com',
            'phone_number' => '3000000000',
            'password'     => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    // ── Login ────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'password', // UserFactory default
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonFragment(['message' => 'Credenciales inválidas.']);
    }

    public function test_login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Sesión cerrada correctamente.']);
    }

    public function test_logout_deletes_the_token_from_the_database(): void
    {
        $user      = User::factory()->create();
        $tokenResult = $user->createToken('auth_token');
        $plainToken  = $tokenResult->plainTextToken;
        $tokenId     = $tokenResult->accessToken->id;

        $this->withToken($plainToken)->postJson('/api/logout')->assertStatus(200);

        // The token record must be gone from the DB — it can no longer authenticate
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $this->postJson('/api/logout')->assertStatus(401);
    }
}
