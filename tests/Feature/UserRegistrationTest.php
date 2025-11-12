<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Enum\UserRole;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration with valid data (customer).
     */
    public function test_user_can_register_as_customer_with_valid_data(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '1234567890',
            'role' => 'customer',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'role',
                ],
                'message',
                'token',
            ])
            ->assertJson(['message' => 'User registered successfully']);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'customer',
        ]);
    }

    /**
     * Test user registration with valid data (organizer) including push token.
     */
    public function test_user_can_register_as_organizer_with_push_token(): void
    {
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'phone' => '0987654321',
            'role' => UserRole::ORGANIZER,
            'push_token' => 'some_push_token_for_jane',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJson(['message' => 'User registered successfully']);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'role' => UserRole::ORGANIZER,
            'push_token' => 'some_push_token_for_jane',
        ]);
    }

    /**
     * Test user registration with invalid data (missing email).
     */
    public function test_user_cannot_register_with_missing_email(): void
    {
        $userData = [
            'name' => 'John Doe',
            'password' => 'password123',
            'role' => UserRole::CUSTOMER,
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user registration with existing email.
     */
    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'role' => UserRole::CUSTOMER,
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user registration with invalid role.
     */
    public function test_user_cannot_register_with_invalid_role(): void
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'invalid_role',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    /**
     * Test user registration with push token that is too long.
     */
    public function test_user_cannot_register_with_long_push_token(): void
    {
        $longPushToken = str_repeat('a', 501); // 501 characters
        $userData = [
            'name' => 'Test User',
            'email' => 'test_long_token@example.com',
            'password' => 'password123',
            'role' => UserRole::CUSTOMER,
            'push_token' => $longPushToken,
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['push_token']);
    }
}
