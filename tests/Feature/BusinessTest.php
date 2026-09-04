<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can create a business', function () {
    $response = $this->postJson('/api/v1/businesses', [
        'name' => 'Test Business',
        'slug' => 'test-business',
        'email' => 'test@business.com',
        'timezone' => 'America/New_York',
        'locale' => 'en',
        'currency' => 'USD',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['business' => ['id', 'uuid', 'name', 'slug']]);

    expect(Business::count())->toBe(1);
    expect(Business::first()->owner_id)->toBe($this->user->id);

    $pivotRole = Business::first()->users()->where('user_id', $this->user->id)->first()->pivot->role;
    expect($pivotRole)->toBe('business-owner');
});

it('generates slug from name', function () {
    $response = $this->postJson('/api/v1/businesses', [
        'name' => 'My Awesome Business',
    ]);

    $response->assertStatus(201);
    expect(Business::first()->slug)->toContain('my-awesome-business');
});

it('can list user businesses', function () {
    $business = Business::factory()->create(['owner_id' => $this->user->id]);

    $this->user->businesses()->attach($business->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/businesses');

    $response->assertStatus(200);
});

it('can show a business', function () {
    $business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($business->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/businesses/{$business->id}");

    $response->assertStatus(200);
    $response->assertJson(['name' => $business->name]);
});

it('can update own business', function () {
    $business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($business->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->putJson("/api/v1/businesses/{$business->id}", [
        'name' => 'Updated Business',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('business.name', 'Updated Business');
});

it('can switch business', function () {
    $business1 = Business::factory()->create(['owner_id' => $this->user->id]);
    $business2 = Business::factory()->create(['owner_id' => $this->user->id]);

    $this->user->businesses()->attach($business1->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->user->businesses()->attach($business2->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->postJson('/api/v1/business/switch', [
        'business_id' => $business2->id,
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('business.id', $business2->id);
});

it('can get current business', function () {
    $business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($business->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    app(\App\Managers\TenantManager::class)->switch($this->user, $business);

    $response = $this->getJson('/api/v1/business/current');

    $response->assertStatus(200);
    $response->assertJson(['id' => $business->id]);
});

it('can delete own business', function () {
    $business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($business->id, [
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->deleteJson("/api/v1/businesses/{$business->id}");

    $response->assertStatus(200);
    expect(Business::count())->toBe(0);
});
