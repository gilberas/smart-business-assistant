<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
});

it('can register a new user', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email']]);
});

it('can login with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'businesses'],
            'token',
            'current_business',
        ]);
});

it('returns no business context when user has no memberships', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(0, 'user.businesses')
        ->assertJsonPath('current_business', null);
});

it('auto-selects a members business on login', function () {
    $business = \App\Models\Business::factory()->create();
    $this->user->businesses()->attach($business->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'user.businesses')
        ->assertJsonPath('current_business.id', $business->id);
});

it('cannot login with invalid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

it('can logout', function () {
    $token = $this->user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => __('auth.logout_successful')]);
});

it('can get authenticated user', function () {
    $token = $this->user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->getJson('/api/v1/auth/user');

    $response->assertStatus(200)
        ->assertJson(['id' => $this->user->id, 'name' => $this->user->name, 'email' => $this->user->email]);
});

it('cannot access protected routes without token', function () {
    $response = $this->getJson('/api/v1/auth/user');

    $response->assertStatus(401);
});

it('can send password reset link', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200);
});

it('cannot register with duplicate email', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('requires password confirmation for registration', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('can update profile', function () {
    $token = $this->user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->putJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
            'phone' => '+1234567890',
            'timezone' => 'America/New_York',
        ]);

    $response->assertStatus(200)
        ->assertJson(['name' => 'Updated Name', 'phone' => '+1234567890', 'timezone' => 'America/New_York']);
});

it('can change password', function () {
    $token = $this->user->createToken('auth-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer $token")
        ->putJson('/api/v1/auth/profile/password', [
            'current_password' => 'password123',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertStatus(200);
});
