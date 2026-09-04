<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $this->owner = User::factory()->create();
    $this->business = Business::factory()->create(['owner_id' => $this->owner->id]);
    $this->business->users()->attach($this->owner->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($this->owner);

    app(\App\Managers\TenantManager::class)->switch($this->owner, $this->business);
});

it('creates a business and stores the onboarding step', function () {
    $response = $this->postJson('/api/v1/businesses', [
        'name' => 'Acme Inc.',
        'industry' => 'technology',
        'onboarding_step' => 'business-info',
    ]);

    $response->assertStatus(201);
    $response->assertJsonPath('business.onboarding_step', 'business-info');
    $response->assertJsonPath('business.onboarding_completed_at', null);

    expect(Business::where('name', 'Acme Inc.')->first()->onboarding_step)->toBe('business-info');
});

it('owner can update the onboarding step', function () {
    $response = $this->postJson("/api/v1/businesses/{$this->business->id}/onboarding", [
        'step' => 'details',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('business.onboarding_step', 'details');
    $response->assertJsonPath('business.onboarding_completed_at', null);

    expect($this->business->fresh()->onboarding_step)->toBe('details');
});

it('owner can complete onboarding', function () {
    $this->business->update(['onboarding_step' => 'preferences', 'onboarding_completed_at' => null]);

    $response = $this->postJson("/api/v1/businesses/{$this->business->id}/onboarding/complete");

    $response->assertStatus(200);
    $response->assertJsonPath('business.onboarding_step', 'preferences');
    expect($response->json('business.onboarding_completed_at'))->not->toBeNull();

    expect($this->business->fresh()->onboarding_completed_at)->not->toBeNull();
});

it('rejects an invalid onboarding step', function () {
    $response = $this->postJson("/api/v1/businesses/{$this->business->id}/onboarding", [
        'step' => 'not-a-step',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['step']);
});

it('viewer cannot update onboarding', function () {
    $viewer = User::factory()->create();
    $this->business->users()->attach($viewer->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($viewer);
    app(\App\Managers\TenantManager::class)->switch($viewer, $this->business);

    $response = $this->postJson("/api/v1/businesses/{$this->business->id}/onboarding", [
        'step' => 'details',
    ]);

    $response->assertStatus(403);
});

it('non-member cannot update another business onboarding', function () {
    $otherOwner = User::factory()->create();
    $otherBusiness = Business::factory()->create(['owner_id' => $otherOwner->id]);
    $otherBusiness->users()->attach($otherOwner->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/businesses/{$otherBusiness->id}/onboarding", [
        'step' => 'details',
    ]);

    $response->assertStatus(403);
});

it('exposes onboarding fields on business detail', function () {
    $this->business->update(['onboarding_step' => 'preferences', 'onboarding_completed_at' => now()]);

    $response = $this->getJson("/api/v1/businesses/{$this->business->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('onboarding_step', 'preferences');
    expect($response->json('onboarding_completed_at'))->not->toBeNull();
});

it('can create a business and complete onboarding in sequence', function () {
    $freshUser = User::factory()->create();
    $this->actingAs($freshUser);

    $createResponse = $this->postJson('/api/v1/businesses', [
        'name' => 'Onboarding Test Business',
        'email' => 'test@onboarding.com',
        'timezone' => 'America/New_York',
        'locale' => 'en',
        'currency' => 'USD',
        'onboarding_step' => 'preferences',
    ]);

    $createResponse->assertStatus(201);
    $businessId = $createResponse->json('business.id');

    $completeResponse = $this->postJson("/api/v1/businesses/{$businessId}/onboarding/complete");

    $completeResponse->assertStatus(200);
    $completeResponse->assertJsonPath('business.onboarding_completed_at', fn ($val) => $val !== null);

    $business = Business::find($businessId);
    expect($business->onboarding_completed_at)->not->toBeNull();
    expect($business->onboarding_step)->toBe('preferences');
});