<?php

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\TokenStatus;
use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WhatsAppAccountFactory extends Factory
{
    protected $model = WhatsAppAccount::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'waba_id' => (string) fake()->randomNumber(9),
            'access_token' => encrypt('EAAx' . Str::random(40)),
            'token_status' => TokenStatus::Active,
            'webhook_secret' => 'whsec_' . bin2hex(random_bytes(32)),
            'status' => WhatsAppAccountStatus::Connected,
            'connected_at' => now(),
            'last_synced_at' => now(),
        ];
    }

    public function disconnected(): static
    {
        return $this->state(fn () => [
            'access_token' => null,
            'status' => WhatsAppAccountStatus::Disconnected,
            'token_status' => TokenStatus::Revoked,
            'connected_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'token_status' => TokenStatus::Expired,
            'status' => WhatsAppAccountStatus::Expired,
        ]);
    }
}
