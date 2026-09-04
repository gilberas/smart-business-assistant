<?php

use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\MembershipActivity;
use App\Models\User;
use App\Support\RoleRegistry;
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

it('owner can invite a new member', function () {
    $response = $this->postJson("/api/v1/team/invitations", [
        'email' => 'newmember@example.com',
        'role' => 'agent',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['invitation' => ['id', 'email', 'role', 'token']]);

    expect(BusinessInvitation::count())->toBe(1);
    expect(BusinessInvitation::first()->status)->toBe('pending');
});

it('generates a unique token for each invitation', function () {
    $this->postJson("/api/v1/team/invitations", [
        'email' => 'member1@example.com',
        'role' => 'agent',
    ]);

    $this->postJson("/api/v1/team/invitations", [
        'email' => 'member2@example.com',
        'role' => 'manager',
    ]);

    $tokens = BusinessInvitation::pluck('token')->toArray();
    expect($tokens[0])->not->toBe($tokens[1]);
});

it('prevents duplicate pending invitation for same email', function () {
    $this->postJson("/api/v1/team/invitations", [
        'email' => 'duplicate@example.com',
        'role' => 'agent',
    ]);

    $response = $this->postJson("/api/v1/team/invitations", [
        'email' => 'duplicate@example.com',
        'role' => 'manager',
    ]);

    $response->assertStatus(422);
});

it('prevents inviting existing member', function () {
    $existingMember = User::factory()->create();
    $this->business->users()->attach($existingMember->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/team/invitations", [
        'email' => $existingMember->email,
        'role' => 'manager',
    ]);

    $response->assertStatus(422);
});

it('lists pending invitations', function () {
    BusinessInvitation::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
    ]);

    $response = $this->getJson("/api/v1/team/invitations");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'invitations');
});

it('owner can cancel pending invitation', function () {
    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
    ]);

    $response = $this->deleteJson("/api/v1/team/invitations/{$invitation->id}");

    $response->assertStatus(200);
    expect($invitation->fresh()->status)->toBe('cancelled');
});

it('can resend invitation', function () {
    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
    ]);

    $response = $this->postJson("/api/v1/team/invitations/{$invitation->id}/resend");

    $response->assertStatus(200);
});

it('user can accept invitation by token', function () {
    $user = User::factory()->create(['email' => 'accept@example.com']);
    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
        'email' => 'accept@example.com',
        'role' => 'manager',
    ]);

    $this->actingAs($user);

    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

    $response->assertStatus(200);
    expect($invitation->fresh()->status)->toBe('accepted');
    expect($this->business->fresh()->users)->toHaveCount(2);
});

it('user cannot accept expired invitation', function () {
    $user = User::factory()->create(['email' => 'expired@example.com']);
    $invitation = BusinessInvitation::factory()->expired()->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
        'email' => 'expired@example.com',
    ]);

    $this->actingAs($user);

    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/accept");

    $response->assertStatus(422);
});

it('user can reject invitation', function () {
    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $this->business->id,
        'invited_by' => $this->owner->id,
        'email' => 'reject@example.com',
    ]);

    $response = $this->postJson("/api/v1/invitations/{$invitation->token}/reject");

    $response->assertStatus(200);
    expect($invitation->fresh()->status)->toBe('cancelled');
});

it('owner can change member role', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->putJson("/api/v1/team/members/{$member->id}/role", [
        'role' => 'manager',
    ]);

    $response->assertStatus(200);

    $pivot = $this->business->users()->where('user_id', $member->id)->first()->pivot;
    expect($pivot->role)->toBe('manager');
});

it('records membership activity on role change', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->putJson("/api/v1/team/members/{$member->id}/role", [
        'role' => 'manager',
    ]);

    expect(MembershipActivity::where('action', 'role_changed')->count())->toBe(1);
});

it('owner can remove member', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->deleteJson("/api/v1/team/members/{$member->id}");

    $response->assertStatus(200);
    expect($this->business->fresh()->users)->toHaveCount(1);
});

it('cannot remove owner', function () {
    $response = $this->deleteJson("/api/v1/team/members/{$this->owner->id}");

    $response->assertStatus(422);
});

it('owner can suspend and restore member', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->postJson("/api/v1/team/members/{$member->id}/suspend")->assertStatus(200);
    expect($this->business->users()->where('user_id', $member->id)->first()->pivot->status)->toBe('suspended');

    $this->postJson("/api/v1/team/members/{$member->id}/restore")->assertStatus(200);
    expect($this->business->users()->where('user_id', $member->id)->first()->pivot->status)->toBe('active');
});

it('cannot suspend owner', function () {
    $response = $this->postJson("/api/v1/team/members/{$this->owner->id}/suspend");
    $response->assertStatus(422);
});

it('owner can transfer ownership to another member', function () {
    $newOwner = User::factory()->create();
    $this->business->users()->attach($newOwner->id, [
        'role' => 'admin',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->postJson("/api/v1/team/transfer-ownership", [
        'user_id' => $newOwner->id,
    ]);

    $response->assertStatus(200);
    expect($this->business->fresh()->owner_id)->toBe($newOwner->id);

    $oldOwnerPivot = $this->business->users()->where('user_id', $this->owner->id)->first()->pivot;
    expect($oldOwnerPivot->role)->toBe('business-admin');

    $newOwnerPivot = $this->business->users()->where('user_id', $newOwner->id)->first()->pivot;
    expect($newOwnerPivot->role)->toBe('business-owner');
});

it('cannot transfer ownership to non-member', function () {
    $nonMember = User::factory()->create();

    $response = $this->postJson("/api/v1/team/transfer-ownership", [
        'user_id' => $nonMember->id,
    ]);

    $response->assertStatus(422);
});

it('member can leave business', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($member);
    app(\App\Managers\TenantManager::class)->switch($member, $this->business);

    $response = $this->postJson("/api/v1/team/leave");

    $response->assertStatus(200);
    expect($this->business->fresh()->users)->toHaveCount(1);
});

it('owner cannot leave business', function () {
    $response = $this->postJson("/api/v1/team/leave");

    $response->assertStatus(422);
});

it('lists team members', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/team/members");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'members');
});

it('records membership activity for various actions', function () {
    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->deleteJson("/api/v1/team/members/{$member->id}");
    expect(MembershipActivity::where('action', 'removed')->count())->toBe(1);
});

it('returns team activity log', function () {
    MembershipActivity::record($this->business->id, $this->owner->id, null, \App\Enums\MembershipActivityType::Joined);
    MembershipActivity::record($this->business->id, $this->owner->id, null, \App\Enums\MembershipActivityType::RoleChanged, [
        'old_role' => 'agent', 'new_role' => 'manager',
    ]);

    $response = $this->getJson("/api/v1/team/activity");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'activity');
});

it('returns available team roles', function () {
    $response = $this->getJson("/api/v1/team/roles");

    $response->assertStatus(200)
        ->assertJsonStructure(['roles']);

    $roleNames = collect($response->json('roles'))->pluck('name')->toArray();
    expect($roleNames)->toContain('business-owner', 'business-admin', 'manager', 'agent', 'viewer');
});

it('non-admin cannot invite members', function () {
    $viewer = User::factory()->create();
    $this->business->users()->attach($viewer->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($viewer);
    app(\App\Managers\TenantManager::class)->switch($viewer, $this->business);

    $response = $this->postJson("/api/v1/team/invitations", [
        'email' => 'someone@example.com',
        'role' => 'agent',
    ]);

    $response->assertStatus(403);
});

it('prevents role escalation by non-owner', function () {
    $admin = User::factory()->create();
    $this->business->users()->attach($admin->id, [
        'role' => 'business-admin',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($admin);
    app(\App\Managers\TenantManager::class)->switch($admin, $this->business);

    $member = User::factory()->create();
    $this->business->users()->attach($member->id, [
        'role' => 'agent',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $response = $this->putJson("/api/v1/team/members/{$member->id}/role", [
        'role' => 'business-owner',
    ]);

    $response->assertStatus(422);
});

it('validates invitation role', function () {
    $response = $this->postJson("/api/v1/team/invitations", [
        'email' => 'test@example.com',
        'role' => 'nonexistent-role',
    ]);

    $response->assertStatus(422);
});
