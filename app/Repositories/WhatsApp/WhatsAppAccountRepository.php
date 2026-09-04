<?php

namespace App\Repositories\WhatsApp;

use App\Enums\WhatsApp\TokenStatus;
use App\Enums\WhatsApp\WhatsAppAccountStatus;
use App\Models\WhatsApp\WhatsAppAccount;

class WhatsAppAccountRepository
{
    public function findByBusinessId(int $businessId): ?WhatsAppAccount
    {
        return WhatsAppAccount::where('business_id', $businessId)->first();
    }

    public function findById(int $id): ?WhatsAppAccount
    {
        return WhatsAppAccount::find($id);
    }

    public function findByUuid(string $uuid): ?WhatsAppAccount
    {
        return WhatsAppAccount::where('uuid', $uuid)->first();
    }

    public function create(array $data): WhatsAppAccount
    {
        return WhatsAppAccount::create($data);
    }

    public function update(WhatsAppAccount $account, array $data): WhatsAppAccount
    {
        $account->update($data);
        return $account->fresh();
    }

    public function delete(WhatsAppAccount $account): void
    {
        $account->delete();
    }

    public function getConnected(): iterable
    {
        return WhatsAppAccount::connected()->get();
    }

    public function getWithExpiringTokens(int $days = 14): iterable
    {
        return WhatsAppAccount::tokenExpiring($days)->get();
    }

    public function markAsConnected(WhatsAppAccount $account, string $wabaId, string $token, ?array $metadata = null): WhatsAppAccount
    {
        return $this->update($account, [
            'waba_id' => $wabaId,
            'access_token' => $token,
            'token_status' => TokenStatus::Active,
            'status' => WhatsAppAccountStatus::Connected,
            'connected_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function markAsDisconnected(WhatsAppAccount $account): WhatsAppAccount
    {
        return $this->update($account, [
            'token_status' => TokenStatus::Revoked,
            'status' => WhatsAppAccountStatus::Disconnected,
            'access_token' => null,
        ]);
    }

    public function updateToken(WhatsAppAccount $account, string $token): WhatsAppAccount
    {
        return $this->update($account, [
            'access_token' => $token,
            'token_status' => TokenStatus::Active,
        ]);
    }

    public function updateWebhookSecret(WhatsAppAccount $account, string $secret): WhatsAppAccount
    {
        return $this->update($account, [
            'webhook_secret' => $secret,
            'webhook_verified_at' => now(),
        ]);
    }
}
