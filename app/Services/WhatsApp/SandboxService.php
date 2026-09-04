<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class SandboxService
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    public function isEnabled(): bool
    {
        return config('whatsapp.sandbox.enabled', false) || app()->environment('local', 'testing');
    }

    public function getSandboxPhone(): ?string
    {
        return config('whatsapp.sandbox.phone_number');
    }

    public function isSandboxMessage(string $phoneNumber): bool
    {
        $sandboxPhone = $this->getSandboxPhone();
        return $sandboxPhone !== null && $phoneNumber === $sandboxPhone;
    }

    public function logSandboxAction(string $action, array $data = []): void
    {
        Log::info("[WhatsApp Sandbox] {$action}", $data);
    }

    public function simulateIncomingMessage(int $businessId, string $from, string $body, string $type = 'text'): array
    {
        $this->logSandboxAction('Simulating incoming message', [
            'business_id' => $businessId,
            'from' => $from,
            'type' => $type,
            'body' => $body,
        ]);

        $account = $this->whatsAppService->getAccountInfo(
            $this->getOrCreateAccount($businessId)
        );

        return [
            'success' => true,
            'simulated' => true,
            'from' => $from,
            'body' => $body,
            'type' => $type,
        ];
    }

    public function simulateStatusUpdate(int $businessId, string $messageId, string $status): array
    {
        $this->logSandboxAction('Simulating status update', [
            'business_id' => $businessId,
            'message_id' => $messageId,
            'status' => $status,
        ]);

        return [
            'success' => true,
            'simulated' => true,
            'message_id' => $messageId,
            'status' => $status,
        ];
    }

    protected function getOrCreateAccount(int $businessId): mixed
    {
        $account = \App\Models\WhatsApp\WhatsAppAccount::where('business_id', $businessId)->first();
        if (!$account) {
            $account = \App\Models\WhatsApp\WhatsAppAccount::create([
                'business_id' => $businessId,
                'waba_id' => 'sandbox_waba',
                'access_token' => 'sandbox_token',
                'status' => 'connected',
                'token_status' => 'active',
                'connected_at' => now(),
            ]);
        }
        return $account;
    }
}
