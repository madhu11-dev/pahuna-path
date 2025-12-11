<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Test user registration with invalid data
     */
    public function test_user_registration_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test duplicate email registration
     */
    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'user',
                'token'
            ]);
    }

    /**
     * Test user login with incorrect password
     */
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);

        // Token should be deleted
        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test staff registration
     */
    public function test_staff_can_register(): void
    {
        $response = $this->postJson('/api/auth/staff/register', [
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.com',
            'utype' => 'STF',
        ]);
    }

    /**
     * Test unapproved staff cannot access protected routes
     */
    public function test_unapproved_staff_cannot_access_staff_routes(): void
    {
        $this->markTestSkipped('Staff approval middleware not implemented yet');
        
        $staff = User::factory()->staff()->create();

        $token = $staff->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/accommodations');

        $response->assertStatus(403);
    }

    /**
     * Test password reset request
     */
    public function test_user_can_request_password_reset(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
    }
}
