<?php

use App\Enums\Conversation\ConversationPriority;
use App\Enums\Conversation\ConversationSource;
use App\Enums\Conversation\ConversationStatus;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\ConversationLabel;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->user->businesses()->attach($this->business, ['role' => 'business-admin']);
    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->actingAs($this->user);

    session(['current_business_id' => $this->business->id]);

    $this->artisan('cache:clear');

    app(\App\Managers\TenantManager::class)->clear();
});

// Conversation CRUD

it('can list conversations', function () {
    Conversation::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can create a conversation', function () {
    $response = $this->postJson('/api/v1/conversations', [
        'customer_id' => $this->customer->id,
        'source' => 'whatsapp',
        'channel' => 'whatsapp',
        'channel_id' => '+15551234567',
        'priority' => 'high',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.source', 'whatsapp')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.channel', 'whatsapp')
        ->assertJsonStructure(['data' => ['uuid', 'status', 'customer']]);
});

it('can show a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $conversation->id);
});

it('can update a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->putJson("/api/v1/conversations/{$conversation->id}", [
        'title' => 'Updated Title',
        'priority' => 'urgent',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.priority', 'urgent');
});

it('can delete a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->deleteJson("/api/v1/conversations/{$conversation->id}");

    $response->assertOk();
    $this->assertSoftDeleted($conversation);
});

// Conversation State Transitions

it('can assign a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    $agent = User::factory()->create();

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/assign", [
        'user_id' => $agent->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'assigned');
});

it('can close a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'assigned',
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/close", [
        'reason' => 'Issue resolved',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'closed');
});

it('can reopen a closed conversation', function () {
    $conversation = Conversation::factory()->closed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/reopen");

    $response->assertOk();
    expect($conversation->fresh()->status)->toBe(ConversationStatus::New);
});

// Conversation Labels

it('can manage conversation labels', function () {
    $labelResponse = $this->postJson('/api/v1/conversation-labels', [
        'name' => 'VIP',
        'color' => '#ff0000',
        'description' => 'Very important person',
    ]);

    $labelResponse->assertCreated();
    $labelId = $labelResponse->json('data.id');

    $listResponse = $this->getJson('/api/v1/conversation-labels');
    $listResponse->assertOk()
        ->assertJsonCount(1, 'data');
});

it('can attach and detach labels to conversations', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    $label = ConversationLabel::factory()->create(['business_id' => $this->business->id]);

    $attachResponse = $this->postJson("/api/v1/conversations/{$conversation->id}/labels", [
        'label_id' => $label->id,
    ]);
    $attachResponse->assertOk();
    expect($conversation->fresh()->labels)->toHaveCount(1);

    $detachResponse = $this->deleteJson("/api/v1/conversations/{$conversation->id}/labels/{$label->id}");
    $detachResponse->assertOk();
    expect($conversation->fresh()->labels)->toHaveCount(0);
});

// Conversation Messages

it('can send a message in a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'content' => 'Hello, how can I help you?',
        'content_type' => 'text',
        'sender_type' => 'agent',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.content', 'Hello, how can I help you?')
        ->assertJsonPath('data.sender_type', 'agent');
});

it('can list conversation messages', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    \App\Models\Message::factory()->count(5)->create([
        'conversation_id' => $conversation->id,
        'business_id' => $this->business->id,
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}/messages");

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

// Conversation Events

it('creates events on conversation actions', function () {
    $createResponse = $this->postJson('/api/v1/conversations', [
        'customer_id' => $this->customer->id,
        'source' => 'whatsapp',
        'channel' => 'whatsapp',
        'channel_id' => '+15551234567',
    ]);

    $conversationId = $createResponse->json('data.id');

    $agent = User::factory()->create();
    $this->postJson("/api/v1/conversations/{$conversationId}/assign", ['user_id' => $agent->id]);
    $this->postJson("/api/v1/conversations/{$conversationId}/close", ['reason' => 'Done']);

    $response = $this->getJson("/api/v1/conversations/{$conversationId}/events");

    $response->assertOk();
    $events = collect($response->json('data'));
    expect($events->pluck('type'))->toContain('created', 'assigned', 'closed');
});

// Conversation Stats

it('can get conversation stats', function () {
    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'new',
    ]);
    Conversation::factory()->count(3)->assigned()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    Conversation::factory()->closed()->count(1)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations/stats');

    $response->assertOk();
    $stats = $response->json();
    expect($stats)->toHaveKey('by_status');
});

// Conversation Filters

it('can filter conversations by status', function () {
    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'new',
    ]);
    Conversation::factory()->closed()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?status=closed');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('can filter conversations by priority', function () {
    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'priority' => 'urgent',
    ]);
    Conversation::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'priority' => 'normal',
    ]);

    $response = $this->getJson('/api/v1/conversations?priority=urgent');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can filter unassigned conversations', function () {
    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    Conversation::factory()->assigned()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?unassigned=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('can filter active conversations', function () {
    Conversation::factory()->count(4)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    Conversation::factory()->closed()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?active=1');

    $response->assertOk()
        ->assertJsonCount(4, 'data');
});

it('can search conversations', function () {
    $customer = Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'John Doe',
    ]);

    Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?search=John');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

// Tenant Isolation

it('enforces tenant isolation for conversations', function () {
    $otherBusiness = Business::factory()->create();
    $otherCustomer = Customer::factory()->create(['business_id' => $otherBusiness->id]);
    Conversation::factory()->create([
        'business_id' => $otherBusiness->id,
        'customer_id' => $otherCustomer->id,
    ]);

    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->getJson('/api/v1/conversations');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('prevents access to conversations from other businesses', function () {
    $otherBusiness = Business::factory()->create();
    $otherCustomer = Customer::factory()->create(['business_id' => $otherBusiness->id]);
    $conversation = Conversation::factory()->create([
        'business_id' => $otherBusiness->id,
        'customer_id' => $otherCustomer->id,
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertForbidden();
});

// UUID Assignment

it('assigns UUID when creating a conversation', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    expect($conversation->uuid)->not->toBeNull();
    expect(Str::isUuid($conversation->uuid))->toBeTrue();
});

// Conversation Model Features

it('touches last message when message is added', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    $message = \App\Models\Message::factory()->create([
        'conversation_id' => $conversation->id,
        'business_id' => $this->business->id,
    ]);

    $conversation->touchLastMessage($message);

    expect($conversation->fresh()->last_message_id)->toBe($message->id);
    expect($conversation->fresh()->message_count)->toBe(1);
});

it('can check if conversation is active', function () {
    $active = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'new',
    ]);
    $closed = Conversation::factory()->closed()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    expect($active->isActive())->toBeTrue();
    expect($closed->isActive())->toBeFalse();
});

// Conversation Status Transitions

it('validates status transitions', function () {
    $fromNew = ConversationStatus::New;
    expect($fromNew->canTransitionTo(ConversationStatus::Assigned))->toBeTrue();
    expect($fromNew->canTransitionTo(ConversationStatus::Archived))->toBeFalse();

    $fromClosed = ConversationStatus::Closed;
    expect($fromClosed->canTransitionTo(ConversationStatus::New))->toBeTrue();
    expect($fromClosed->canTransitionTo(ConversationStatus::Assigned))->toBeFalse();

    $fromArchived = ConversationStatus::Archived;
    expect($fromArchived->canTransitionTo(ConversationStatus::Closed))->toBeTrue();
    expect($fromArchived->canTransitionTo(ConversationStatus::New))->toBeFalse();
});

// Conversation Label Business Isolation

it('enforces business isolation for labels', function () {
    ConversationLabel::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Support',
    ]);

    $otherBusiness = Business::factory()->create();
    ConversationLabel::factory()->create([
        'business_id' => $otherBusiness->id,
        'name' => 'Sales',
    ]);

    $response = $this->getJson('/api/v1/conversation-labels');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Support');
});

it('can update a label', function () {
    $label = ConversationLabel::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Original',
    ]);

    $response = $this->putJson("/api/v1/conversation-labels/{$label->id}", [
        'name' => 'Updated Label',
        'color' => '#00ff00',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Label')
        ->assertJsonPath('data.color', '#00ff00');
});

it('can delete a label', function () {
    $label = ConversationLabel::factory()->create([
        'business_id' => $this->business->id,
    ]);

    $response = $this->deleteJson("/api/v1/conversation-labels/{$label->id}");

    $response->assertOk();
    expect(ConversationLabel::find($label->id))->toBeNull();
});

// Message with Attachments Flow

it('can send a message with attachments', function () {
    $conversation = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);

    // Send message
    $msgResponse = $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'content' => 'Check this document',
        'content_type' => 'document',
        'sender_type' => 'agent',
    ]);

    $msgResponse->assertCreated();
    $messageId = $msgResponse->json('data.id');

    // Create attachment directly
    \App\Models\MessageAttachment::factory()->document()->create([
        'message_id' => $messageId,
    ]);

    $showResponse = $this->getJson("/api/v1/conversations/{$conversation->id}/messages");

    $showResponse->assertOk();
});

// Customer Scope Filter

it('can filter conversations by customer', function () {
    $customer2 = Customer::factory()->create(['business_id' => $this->business->id]);

    Conversation::factory()->count(2)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $customer2->id,
    ]);

    $response = $this->getJson('/api/v1/conversations?customer_id=' . $this->customer->id);

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

// Message History inline

it('maintains event history for conversation lifecycle', function () {
    $createResponse = $this->postJson('/api/v1/conversations', [
        'customer_id' => $this->customer->id,
        'source' => 'whatsapp',
        'channel' => 'whatsapp',
        'channel_id' => '+15551234567',
    ]);

    $conversationId = $createResponse->json('data.id');

    $agent = User::factory()->create();
    $this->postJson("/api/v1/conversations/{$conversationId}/assign", ['user_id' => $agent->id]);
    $this->postJson("/api/v1/conversations/{$conversationId}/close", ['reason' => 'Completed']);
    $this->postJson("/api/v1/conversations/{$conversationId}/reopen");

    $response = $this->getJson("/api/v1/conversations/{$conversationId}/events");
    $response->assertOk();

    $eventTypes = collect($response->json('data'))->pluck('type')->values();
    expect($eventTypes)->toContain('created', 'assigned', 'closed', 'reopened');
});
