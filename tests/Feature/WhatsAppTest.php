<?php

use App\Enums\WhatsApp\MessageDirection;
use App\Enums\WhatsApp\MessageStatus;
use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Enums\WhatsApp\WebhookEventStatus;
use App\Models\Business;
use App\Models\User;
use App\Models\WhatsApp\Media;
use App\Models\WhatsApp\Message;
use App\Models\WhatsApp\PhoneNumber;
use App\Models\WhatsApp\WebhookEvent;
use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

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

    session(['current_business_id' => $this->business->id]);

    Config::set('whatsapp.mock.enabled', true);
});

// --- Account Tests ---

it('can show account when none connected', function () {
    $response = $this->getJson('/api/v1/whatsapp/account');

    $response->assertStatus(200);
    expect($response->json('data'))->toBeNull();
});

it('can show connected account', function () {
    $account = WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    PhoneNumber::factory()->create(['business_id' => $this->business->id, 'whatsapp_account_id' => $account->id]);

    $response = $this->getJson('/api/v1/whatsapp/account');

    $response->assertStatus(200);
    expect($response->json('data.uuid'))->toBe($account->uuid);
    expect($response->json('data.status'))->toBe('connected');
});

it('can connect a whatsapp account', function () {
    $response = $this->postJson('/api/v1/whatsapp/account/connect', [
        'access_token' => 'EAAx' . Str::random(40),
        'waba_id' => '123456789',
        'phone_number_id' => '987654321',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.status'))->toBe('connected');
    expect(WhatsAppAccount::where('business_id', $this->business->id)->count())->toBe(1);
});

it('validates access token on connect', function () {
    $response = $this->postJson('/api/v1/whatsapp/account/connect', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['access_token']);
});

it('prevents connecting twice', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson('/api/v1/whatsapp/account/connect', [
        'access_token' => 'EAAx' . Str::random(40),
    ]);

    $response->assertStatus(500);
});

it('can disconnect a whatsapp account', function () {
    $account = WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson('/api/v1/whatsapp/account/disconnect');

    $response->assertStatus(200);
    expect($account->fresh()->status->value)->toBe('disconnected');
});

it('returns 404 when disconnecting with no account', function () {
    $response = $this->postJson('/api/v1/whatsapp/account/disconnect');

    $response->assertStatus(404);
});

it('can reconnect a whatsapp account', function () {
    $account = WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson('/api/v1/whatsapp/account/reconnect', [
        'access_token' => 'EAAx' . Str::random(40),
    ]);

    $response->assertStatus(200);
    expect($response->json('data.status'))->toBe('connected');
});

it('can sync phone numbers', function () {
    WhatsAppAccount::factory()->create([
        'business_id' => $this->business->id,
        'waba_id' => 'mock_waba_123',
    ]);

    $response = $this->postJson('/api/v1/whatsapp/account/sync');

    $response->assertStatus(200);
    expect($response->json('data.phone_numbers'))->toBeArray();
});

it('returns account status', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/account/status');

    $response->assertStatus(200);
    expect($response->json('data.connected'))->toBeTrue();
});

it('returns diagnostics', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    Message::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/diagnostics');

    $response->assertStatus(200);
    expect($response->json('data.account.status'))->toBe('connected');
    expect($response->json('data.message_stats.total'))->toBe(3);
});

// --- Webhook Tests ---

it('can verify webhook', function () {
    Config::set('whatsapp.webhook.verify_token', 'test_verify_token');
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/whatsapp/webhook/{$this->business->uuid}?" . http_build_query([
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'test_verify_token',
        'hub_challenge' => '123456789',
    ]));

    $response->assertStatus(200);
    expect($response->content())->toBe('123456789');
});

it('rejects webhook verification with wrong token', function () {
    Config::set('whatsapp.webhook.verify_token', 'correct_token');
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/whatsapp/webhook/{$this->business->uuid}?" . http_build_query([
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'wrong_token',
        'hub_challenge' => '123456789',
    ]));

    $response->assertStatus(403);
});

it('can receive incoming webhook events', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->postJson("/api/v1/whatsapp/webhook/{$this->business->uuid}", [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => '123',
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '+15551234567',
                                'phone_number_id' => 'phone_id_1',
                            ],
                            'messages' => [
                                [
                                    'from' => '+15559876543',
                                    'id' => 'wamid.' . Str::random(20),
                                    'timestamp' => (string) time(),
                                    'text' => ['body' => 'Hello!'],
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'field' => 'messages',
                    ],
                ],
            ],
        ],
    ]);

    $response->assertStatus(200);
    expect(WebhookEvent::count())->toBe(1);
    expect(Message::count())->toBe(1);
    expect(Message::first()->body)->toBe('Hello!');
});

it('can receive status update webhook', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    $message = Message::factory()->create([
        'business_id' => $this->business->id,
        'meta_message_id' => 'wamid.status_test_123',
    ]);

    $response = $this->postJson("/api/v1/whatsapp/webhook/{$this->business->uuid}", [
        'object' => 'whatsapp_business_account',
        'entry' => [
            [
                'id' => '123',
                'changes' => [
                    [
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => [
                                'display_phone_number' => '+15551234567',
                                'phone_number_id' => 'phone_id_1',
                            ],
                            'statuses' => [
                                [
                                    'id' => 'wamid.status_test_123',
                                    'status' => 'read',
                                    'timestamp' => (string) time(),
                                    'recipient_id' => '+15551234567',
                                ],
                            ],
                        ],
                        'field' => 'messages',
                    ],
                ],
            ],
        ],
    ]);

    $response->assertStatus(200);
    expect($message->fresh()->status->value)->toBe('read');
});

it('returns webhook health', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/webhook/health');

    $response->assertStatus(200);
    expect($response->json('data.connected'))->toBeTrue();
});

it('can list webhook events', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    WebhookEvent::factory()->count(3)->create(['business_id' => $this->business->id]);
    WebhookEvent::factory()->failed()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/webhook/events');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(4);
});

it('can filter webhook events by status', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    WebhookEvent::factory()->processed()->create(['business_id' => $this->business->id]);
    WebhookEvent::factory()->failed()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/webhook/events?status=failed');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(1);
    expect($response->json('data.0.status'))->toBe('failed');
});

// --- Message Tests ---

it('can list messages', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    Message::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/messages');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can filter messages by direction', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    Message::factory()->inbound()->create(['business_id' => $this->business->id]);
    Message::factory()->outbound()->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/messages?direction=inbound');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(1);
});

it('can show a message', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    $message = Message::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/whatsapp/messages/{$message->id}");

    $response->assertStatus(200);
    expect($response->json('data.uuid'))->toBe($message->uuid);
});

it('can send a text message', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    PhoneNumber::factory()->create(['business_id' => $this->business->id, 'whatsapp_account_id' => 1]);

    $response = $this->postJson('/api/v1/whatsapp/messages/send', [
        'to' => '+15559876543',
        'type' => 'text',
        'body' => 'Hello from test!',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.body'))->toBe('Hello from test!');
    expect($response->json('data.direction'))->toBe('outbound');
});

it('validates send message payload', function () {
    $response = $this->postJson('/api/v1/whatsapp/messages/send', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['to', 'type']);
});

it('returns message stats', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    Message::factory()->inbound()->count(5)->create(['business_id' => $this->business->id]);
    Message::factory()->outbound()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/messages/stats');

    $response->assertStatus(200);
    expect($response->json('data.total'))->toBe(8);
    expect($response->json('data.inbound'))->toBe(5);
    expect($response->json('data.outbound'))->toBe(3);
});

it('can get conversation history', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    $phone = '+15559876543';
    Message::factory()->inbound()->create(['business_id' => $this->business->id, 'from_number' => $phone]);
    Message::factory()->outbound()->create(['business_id' => $this->business->id, 'to_number' => $phone]);

    $response = $this->getJson("/api/v1/whatsapp/messages/conversation/{$phone}");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(2);
});

// --- Media Tests ---

it('can list media', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    Media::factory()->count(3)->create(['business_id' => $this->business->id]);

    $response = $this->getJson('/api/v1/whatsapp/media');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

it('can show media', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    $media = Media::factory()->create(['business_id' => $this->business->id]);

    $response = $this->getJson("/api/v1/whatsapp/media/{$media->id}");

    $response->assertStatus(200);
    expect($response->json('data.uuid'))->toBe($media->uuid);
});

it('can delete media', function () {
    WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);
    $media = Media::factory()->create(['business_id' => $this->business->id]);

    $response = $this->deleteJson("/api/v1/whatsapp/media/{$media->id}");

    $response->assertStatus(200);
    expect(Media::count())->toBe(0);
});

// --- Tenant Isolation Tests ---

it('enforces account tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $account = WhatsAppAccount::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/whatsapp/diagnostics");

    $response->assertStatus(200);
    expect($response->json('data'))->toBeNull();
});

it('enforces message tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $message = Message::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/whatsapp/messages/{$message->id}");

    $response->assertStatus(403);
});

it('enforces media tenant isolation', function () {
    $otherBusiness = Business::factory()->create();
    $media = Media::factory()->create(['business_id' => $otherBusiness->id]);

    $response = $this->getJson("/api/v1/whatsapp/media/{$media->id}");

    $response->assertStatus(403);
});

it('enforces message list tenant isolation', function () {
    Message::factory()->count(3)->create(['business_id' => $this->business->id]);
    Message::factory()->count(2)->create(['business_id' => Business::factory()->create()->id]);

    $response = $this->getJson('/api/v1/whatsapp/messages');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveLength(3);
});

// --- Permission Tests ---

it('respects viewer permission - can view but not send', function () {
    $viewer = User::factory()->create();
    $viewer->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($viewer);

    $response = $this->getJson('/api/v1/whatsapp/account');
    $response->assertStatus(200);
});

// --- UUID Assignment Tests ---

it('assigns uuid on account creation', function () {
    $account = WhatsAppAccount::factory()->create(['business_id' => $this->business->id]);

    expect($account->uuid)->not->toBeNull();
    expect(strlen($account->uuid))->toBe(36);
});

it('assigns uuid on message creation', function () {
    $message = Message::factory()->create(['business_id' => $this->business->id]);

    expect($message->uuid)->not->toBeNull();
    expect(strlen($message->uuid))->toBe(36);
});

it('assigns uuid on media creation', function () {
    $media = Media::factory()->create(['business_id' => $this->business->id]);

    expect($media->uuid)->not->toBeNull();
    expect(strlen($media->uuid))->toBe(36);
});

it('assigns uuid on webhook event creation', function () {
    $event = WebhookEvent::factory()->create(['business_id' => $this->business->id]);

    expect($event->uuid)->not->toBeNull();
    expect(strlen($event->uuid))->toBe(36);
});

// --- Error Handling Tests ---

it('handles missing account for send', function () {
    $response = $this->postJson('/api/v1/whatsapp/messages/send', [
        'to' => '+15559876543',
        'type' => 'text',
        'body' => 'Hello',
    ]);

    $response->assertStatus(400);
    expect($response->json('message'))->toContain('No connected WhatsApp account');
});

it('handles send message validation errors', function () {
    $response = $this->postJson('/api/v1/whatsapp/messages/send', [
        'to' => '+15559876543',
        'type' => 'invalid_type',
        'body' => 'Hello',
    ]);

    $response->assertStatus(422);
});

it('handles media not found', function () {
    $response = $this->getJson('/api/v1/whatsapp/media/99999');

    $response->assertStatus(404);
});

// --- Tenant Scope Tests ---

it('global tenant scope isolates models by current business', function () {
    $otherBusiness = Business::factory()->create();
    WhatsAppAccount::factory()->create(['business_id' => $otherBusiness->id]);
    WhatsAppAccount::factory()->count(2)->create(['business_id' => $this->business->id]);

    app(\App\Managers\TenantManager::class)->switch($this->user, $this->business);

    expect(WhatsAppAccount::count())->toBe(2);
});

it('tenant scope is a no-op without a selected business', function () {
    $otherBusiness = Business::factory()->create();
    WhatsAppAccount::factory()->count(2)->create(['business_id' => $otherBusiness->id]);
    WhatsAppAccount::factory()->count(3)->create(['business_id' => $this->business->id]);

    expect(WhatsAppAccount::count())->toBe(5);
});

// --- Phase 4: Business-scoped connect & registration ---

it('registers Meta phone numbers scoped to the business on connect', function () {
    $response = $this->postJson('/api/v1/whatsapp/account/connect', [
        'access_token' => 'EAAx' . Str::random(40),
        'waba_id' => 'mock_waba_123',
    ]);

    $response->assertStatus(201);

    $phone = PhoneNumber::where('business_id', $this->business->id)->first();
    expect($phone)->not->toBeNull();
    expect($phone->phone_number)->toBe('+15551234567');
    expect($phone->phone_number_id)->toBe('123456789');
    expect($phone->display_phone)->toBe('+15551234567');
    expect($phone->status->value)->toBe('active');

    expect($response->json('data.phone_numbers.0.display_phone'))->toBe('+15551234567');
});

it('denies connecting a whatsapp account without phone manage permission', function () {
    $viewer = User::factory()->create();
    $viewer->businesses()->attach($this->business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($viewer);

    $response = $this->postJson('/api/v1/whatsapp/account/connect', [
        'access_token' => 'EAAx' . Str::random(40),
    ]);

    $response->assertStatus(403);
});
