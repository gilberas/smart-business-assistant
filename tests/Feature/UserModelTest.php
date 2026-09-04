<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a user', function () {
    $user = User::factory()->unverified()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->isVerified())->toBeFalse()
        ->and($user->isActive())->toBeTrue();
});

it('can mark user as verified', function () {
    $user = User::factory()->unverified()->create();

    expect($user->isVerified())->toBeFalse();

    $user->markEmailAsVerified();

    expect($user->isVerified())->toBeTrue();
});

it('can mark user as logged in', function () {
    $user = User::factory()->create();

    expect($user->last_login_at)->toBeNull();

    $user->markAsLoggedIn('127.0.0.1');

    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->toBe('127.0.0.1');
});

it('can get avatar url', function () {
    $user = User::factory()->create();

    expect($user->avatar_url)->toBeNull();

    $user->update(['avatar' => 'avatars/test.jpg']);
    $user->refresh();

    expect($user->avatar_url)->toContain('avatars/test.jpg');
});

it('has active scope', function () {
    User::factory()->create(['status' => 'active']);
    User::factory()->create(['status' => 'inactive']);

    $activeUsers = User::active()->get();

    expect($activeUsers)->toHaveCount(1);
});

it('has verified scope', function () {
    User::factory()->create(['email_verified_at' => now()]);
    User::factory()->unverified()->create();

    $verifiedUsers = User::verified()->get();

    expect($verifiedUsers)->toHaveCount(1);
});
