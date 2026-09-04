<?php

use App\Contracts\AuthorizationServiceInterface;
use App\Enums\ActivityType;
use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($this->business->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->session(['current_business_id' => $this->business->id]);

    $this->customerData = [
        'name' => 'John Doe',
        'phone' => '+1234567890',
        'email' => 'john@example.com',
        'status' => 'active',
        'source' => 'manual',
        'customer_type' => 'individual',
        'lifecycle_stage' => 'lead',
        'timezone' => 'America/New_York',
        'preferred_language' => 'en',
    ];
});

it('can create a customer', function () {
    $response = $this->postJson('/api/v1/customers', $this->customerData);

    $response->assertStatus(201);
    $response->assertJsonFragment(['name' => 'John Doe']);

    expect(Customer::where('business_id', $this->business->id)->count())->toBe(1);
});

it('requires name to create customer', function () {
    $response = $this->postJson('/api/v1/customers', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
});

it('can list customers', function () {
    Customer::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/customers');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

it('can show a customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/customers/{$customer->uuid}");

    $response->assertStatus(200);
    $response->assertJsonFragment(['uuid' => $customer->uuid]);
});

it('can update a customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->putJson("/api/v1/customers/{$customer->uuid}", [
        'name' => 'Jane Doe',
        'phone' => '+0987654321',
    ]);

    $response->assertStatus(200);
    expect($customer->fresh()->name)->toBe('Jane Doe');
});

it('can delete a customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/customers/{$customer->uuid}");

    $response->assertStatus(200);
    expect(Customer::where('business_id', $this->business->id)->count())->toBe(0);
});

it('validates unique phone per business', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'phone' => '+1234567890',
    ]);

    $response = $this->postJson('/api/v1/customers', $this->customerData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['phone']);
});

it('validates unique email per business', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'email' => 'john@example.com',
    ]);

    $response = $this->postJson('/api/v1/customers', $this->customerData);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

it('enforces tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $customer = Customer::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/customers/{$customer->uuid}");

    $response->assertStatus(403);
});

it('enforces tenant isolation on list', function () {
    Customer::factory()->create(['business_id' => $this->business->id]);
    Customer::factory()->create(['business_id' => Business::factory()->create()->id]);

    $response = $this->getJson('/api/v1/customers');

    $response->assertJsonCount(1, 'data');
});

it('can search customers by name', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'John Doe']);
    Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Jane Smith']);

    $response = $this->getJson('/api/v1/customers/search?q=John');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

it('can search customers by phone', function () {
    Customer::factory()->create(['business_id' => $this->business->id, 'phone' => '+1234567890']);
    Customer::factory()->create(['business_id' => $this->business->id, 'phone' => '+0987654321']);

    $response = $this->getJson('/api/v1/customers/search?q=123456');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

it('can filter customers by status', function () {
    Customer::factory()->active()->create(['business_id' => $this->business->id]);
    Customer::factory()->archived()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/customers?status=archived');

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

it('can get customer stats', function () {
    Customer::factory()->count(3)->active()->create(['business_id' => $this->business->id]);
    Customer::factory()->archived()->create(['business_id' => $this->business->id]);
    Customer::factory()->lead()->create(['business_id' => $this->business->id, 'status' => 'active']);

    $response = $this->getJson('/api/v1/customers/stats');

    $response->assertStatus(200);
    expect($response->json('total'))->toBe(5);
    expect($response->json('active'))->toBe(4);
    expect($response->json('archived'))->toBe(1);
});

it('can archive a customer', function () {
    $customer = Customer::factory()->active()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/archive");

    $response->assertStatus(200);
    expect($customer->fresh()->status)->toBe('archived');
});

it('can restore an archived customer', function () {
    $customer = Customer::factory()->archived()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/restore");

    $response->assertStatus(200);
    expect($customer->fresh()->status)->toBe('active');
});

it('creates activity on customer creation', function () {
    $response = $this->postJson('/api/v1/customers', $this->customerData);

    $customer = Customer::first();
    expect($customer->activity()->where('type', ActivityType::Created->value)->count())->toBe(1);
});

it('creates activity on customer archive', function () {
    $customer = Customer::factory()->active()->create(['business_id' => $this->business->id]);

    $this->postJson("/api/v1/customers/{$customer->uuid}/archive");

    expect($customer->activity()->where('type', ActivityType::Archived->value)->count())->toBe(1);
});

it('can view customer activity', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/customers/{$customer->uuid}/activity");

    $response->assertStatus(200);
});

// --- Tag Tests ---

it('can create a tag', function () {
    $response = $this->postJson('/api/v1/tags', [
        'name' => 'VIP',
        'color' => '#ff0000',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.name'))->toBe('VIP');
});

it('can list tags', function () {
    $this->postJson('/api/v1/tags', ['name' => 'VIP']);
    $this->postJson('/api/v1/tags', ['name' => 'Regular']);

    $response = $this->getJson('/api/v1/tags');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('can attach tags to customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $this->postJson('/api/v1/tags', ['name' => 'VIP']);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/tags/attach", [
        'tags' => ['VIP'],
    ]);

    $response->assertStatus(200);
    expect($customer->fresh()->load('tags')->tags->count())->toBe(1);
});

it('can detach tags from customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $tag = \App\Models\CustomerTag::factory()->create(['business_id' => $this->business->id, 'name' => 'VIP']);
    $customer->tags()->attach($tag);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/tags/detach", [
        'tags' => ['VIP'],
    ]);

    $response->assertStatus(200);
    expect($customer->fresh()->load('tags')->tags->count())->toBe(0);
});

it('enforces tag tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $tag = \App\Models\CustomerTag::factory()->create(['business_id' => $otherBusiness->id, 'name' => 'VIP']);

    $response = $this->putJson("/api/v1/tags/{$tag->id}", ['name' => 'Updated']);

    $response->assertStatus(403);
});

// --- Note Tests ---

it('can add a note to customer', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/notes", [
        'content' => 'This is a test note.',
    ]);

    $response->assertStatus(201);
    expect($customer->fresh()->load('notes')->notes->count())->toBe(1);
});

it('can list customer notes', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $customer->notes()->create(['business_id' => $this->business->id, 'author_id' => $this->user->id, 'content' => 'Note 1']);
    $customer->notes()->create(['business_id' => $this->business->id, 'author_id' => $this->user->id, 'content' => 'Note 2']);

    $response = $this->getJson("/api/v1/customers/{$customer->uuid}/notes");

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

it('can pin and unpin a note', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $note = $customer->notes()->create(['business_id' => $this->business->id, 'author_id' => $this->user->id, 'content' => 'Important note']);

    $response = $this->postJson("/api/v1/customers/{$customer->uuid}/notes/{$note->id}/pin");

    $response->assertStatus(200);
    expect($note->fresh()->is_pinned)->toBeTrue();

    $this->postJson("/api/v1/customers/{$customer->uuid}/notes/{$note->id}/pin");
    expect($note->fresh()->is_pinned)->toBeFalse();
});

it('can delete a note', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $note = $customer->notes()->create(['business_id' => $this->business->id, 'author_id' => $this->user->id, 'content' => 'Temporary note']);

    $response = $this->deleteJson("/api/v1/customers/{$customer->uuid}/notes/{$note->id}");

    $response->assertStatus(200);
    expect($customer->fresh()->load('notes')->notes->count())->toBe(0);
});

// --- Merge Tests ---

it('can merge two customers', function () {
    $primary = Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Primary']);
    $duplicate = Customer::factory()->create(['business_id' => $this->business->id, 'name' => 'Duplicate']);

    $response = $this->postJson('/api/v1/customers/merge', [
        'primary_id' => $primary->id,
        'duplicate_id' => $duplicate->id,
    ]);

    $response->assertStatus(200);
    expect(Customer::where('business_id', $this->business->id)->count())->toBe(1);
    expect(Customer::find($duplicate->id))->toBeNull();
});

it('prevents merging customer with itself', function () {
    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson('/api/v1/customers/merge', [
        'primary_id' => $customer->id,
        'duplicate_id' => $customer->id,
    ]);

    $response->assertStatus(422);
});

// --- Permission Tests ---

it('respects customer view permission', function () {
    $user = User::factory()->create();
    $user->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($user);

    $customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/customers/{$customer->uuid}");
    $response->assertStatus(200);
});

it('accepts customers from any business member', function () {
    $user = User::factory()->create();
    $user->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($user);

    $response = $this->postJson('/api/v1/customers', $this->customerData);
    $response->assertStatus(201);

    $customer = Customer::first();
    $response = $this->getJson("/api/v1/customers/{$customer->uuid}");
    $response->assertStatus(200);
});

it('assigns uuid on creation', function () {
    $response = $this->postJson('/api/v1/customers', $this->customerData);

    $customer = Customer::first();
    expect($customer->uuid)->not->toBeNull();
    expect(strlen($customer->uuid))->toBe(36);
});

it('creates customer with tags array', function () {
    $tag = \App\Models\CustomerTag::factory()->create(['business_id' => $this->business->id, 'name' => 'VIP']);

    $response = $this->postJson('/api/v1/customers', array_merge($this->customerData, [
        'tags' => ['VIP'],
    ]));

    $response->assertStatus(201);
    expect(Customer::with('tags')->first()->tags->count())->toBe(1);
});

it('creates customer with address', function () {
    $response = $this->postJson('/api/v1/customers', array_merge($this->customerData, [
        'address' => [
            'label' => 'Home', 'street' => '123 Main St', 'city' => 'New York', 'country' => 'US', 'is_primary' => true,
        ],
    ]));

    $response->assertStatus(201);
    expect(Customer::first()->addresses->count())->toBe(1);
    expect(Customer::first()->primaryAddress->city)->toBe('New York');
});
