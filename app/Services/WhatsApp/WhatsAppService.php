<?php

namespace App\Services\WhatsApp;

use App\Events\WhatsApp\WhatsAppAccountConnected;
use App\Events\WhatsApp\WhatsAppAccountDisconnected;
use App\Models\WhatsApp\Message;
use App\Models\WhatsApp\PhoneNumber;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Repositories\WhatsApp\MessageRepository;
use App\Repositories\WhatsApp\PhoneNumberRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function __construct(
        private readonly WhatsAppAccountRepository $accountRepo,
        private readonly PhoneNumberRepository $phoneNumberRepo,
        private readonly MessageRepository $messageRepo,
        private readonly WhatsAppApiClient $apiClient,
        private readonly TenantService $tenantService,
    ) {}

    public function connect(int $businessId, string $accessToken, ?string $wabaId = null, ?string $phoneNumberId = null): WhatsAppAccount
    {
        $existing = $this->accountRepo->findByBusinessId($businessId);
        if ($existing) {
            throw new \RuntimeException('A WhatsApp account is already connected for this business');
        }

        $encryptedToken = $this->encryptToken($accessToken);

        $account = $this->accountRepo->create([
            'business_id' => $businessId,
            'access_token' => $encryptedToken,
            'token_status' => 'active',
            'status' => 'connected',
            'connected_at' => now(),
        ]);

        if ($wabaId) {
            $account = $this->accountRepo->markAsConnected($account, $wabaId, $encryptedToken);
        }

        $webhookSecret = $this->generateWebhookSecret();
        $account = $this->accountRepo->updateWebhookSecret($account, $webhookSecret);

        try {
            $this->syncPhoneNumbers($account);
        } catch (\Throwable $e) {
            Log::warning('Failed to sync phone numbers on connect', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        WhatsAppAccountConnected::dispatch($account, $wabaId ?? '');

        return $account;
    }

    public function verify(string $accessToken, ?string $wabaId = null, ?string $phoneNumberId = null): array
    {
        if (empty($accessToken)) {
            throw new \InvalidArgumentException('Access token is required');
        }

        if ($wabaId) {
            $response = $this->apiClient->get($accessToken, "/{$wabaId}", [
                'fields' => 'id,name,currency,timezone_id',
            ]);

            if (($response['status_code'] ?? 0) !== 200) {
                $error = $response['body']['error']['message']
                    ?? 'The access token or WABA ID is invalid.';
                throw new \RuntimeException($error);
            }

            $phoneNumbers = [];
            try {
                $pn = $this->apiClient->getPhoneNumbers($accessToken, $wabaId);
                if (($pn['status_code'] ?? 0) === 200) {
                    $phoneNumbers = $pn['body']['data'] ?? [];
                }
            } catch (\Throwable $e) {
                // Non-fatal: token is valid even if phone numbers can't be read yet.
            }

            return [
                'valid' => true,
                'waba' => $response['body'],
                'phone_numbers' => $phoneNumbers,
            ];
        }

        return [
            'valid' => true,
            'note' => 'Provide a WABA ID to fully validate the token against Meta.',
        ];
    }

    public function disconnect(WhatsAppAccount $account): void
    {
        $this->accountRepo->markAsDisconnected($account);
        WhatsAppAccountDisconnected::dispatch($account);
    }

    public function reconnect(WhatsAppAccount $account, string $newToken): WhatsAppAccount
    {
        $encryptedToken = $this->encryptToken($newToken);
        $account = $this->accountRepo->updateToken($account, $encryptedToken);

        $account = $this->accountRepo->update($account, [
            'status' => 'connected',
            'connected_at' => now(),
        ]);

        try {
            $this->syncPhoneNumbers($account);
        } catch (\Throwable $e) {
            Log::warning('Failed to sync phone numbers on reconnect', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $account;
    }

    public function syncPhoneNumbers(WhatsAppAccount $account): array
    {
        $token = $this->decryptToken($account->access_token);
        $wabaId = $account->waba_id;

        if (!$wabaId) {
            throw new \RuntimeException('WABA ID is not set for this account');
        }

        $response = $this->apiClient->getPhoneNumbers($token, $wabaId);

        if (($response['status_code'] ?? 0) !== 200) {
            throw new \RuntimeException('Failed to fetch phone numbers from Meta');
        }

        $data = $response['body']['data'] ?? [];

        return $this->phoneNumberRepo->syncFromMeta($account->business_id, $account->id, $data);
    }

    public function sendMessage(Message $message): array
    {
        $account = $message->whatsappAccount;
        if (!$account) {
            $account = $this->accountRepo->findByBusinessId($message->business_id);
        }

        if (!$account || !$account->isConnected()) {
            throw new \RuntimeException('No connected WhatsApp account found');
        }

        $token = $this->decryptToken($account->access_token);

        $phoneNumber = $message->phone_number_id;
        if (!$phoneNumber) {
            $primaryPhone = $this->phoneNumberRepo->findByBusinessId($message->business_id)->first();
            if (!$primaryPhone || !$primaryPhone->phone_number_id) {
                throw new \RuntimeException('No phone number with Meta ID found for sending');
            }
            $phoneNumber = $primaryPhone->phone_number_id;
        }

        $to = $message->to_number;
        $replyToId = $message->reply_to_message_id
            ? Message::find($message->reply_to_message_id)?->meta_message_id
            : null;

        $response = match ($message->message_type?->value ?? $message->message_type) {
            'text' => $this->apiClient->sendTextMessage($token, $phoneNumber, $to, $message->body ?? '', $replyToId),
            'image', 'audio', 'video', 'document', 'sticker' => $this->apiClient->sendMediaMessage(
                $token, $phoneNumber, $to,
                $message->message_type->value ?? $message->message_type,
                $message->media_id ?? $message->media_url ?? '',
                $message->caption, $replyToId
            ),
            'template' => $this->apiClient->sendTemplateMessage(
                $token, $phoneNumber, $to,
                $message->template_name ?? '',
                $message->template_language ?? 'en',
                $message->template_params ?? []
            ),
            'location' => $this->sendLocationMessage($token, $phoneNumber, $to, $message),
            default => $this->apiClient->sendTextMessage($token, $phoneNumber, $to, $message->body ?? '', $replyToId),
        };

        return $response;
    }

    protected function sendLocationMessage(string $token, string $phoneNumberId, string $to, Message $message): array
    {
        $location = $message->body ? json_decode($message->body, true) : [];

        return $this->apiClient->sendLocation(
            $token, $phoneNumberId, $to,
            (float) ($location['latitude'] ?? 0),
            (float) ($location['longitude'] ?? 0),
            $location['name'] ?? '',
            $location['address'] ?? ''
        );
    }

    public function markAsRead(WhatsAppAccount $account, string $messageId): array
    {
        $token = $this->decryptToken($account->access_token);
        $primaryPhone = $this->phoneNumberRepo->findByBusinessId($account->business_id)->first();

        if (!$primaryPhone || !$primaryPhone->phone_number_id) {
            return [];
        }

        return $this->apiClient->markAsRead($token, $primaryPhone->phone_number_id, $messageId);
    }

    public function getAccountInfo(WhatsAppAccount $account): ?array
    {
        $token = $this->decryptToken($account->access_token);
        $wabaId = $account->waba_id;

        if (!$wabaId || !$token) {
            return null;
        }

        try {
            $response = $this->apiClient->get($token, "/{$wabaId}", [
                'fields' => 'id,name,currency,timezone_id,message_template_namespace,owner_business_info',
            ]);

            return $response['body'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Failed to fetch WABA info', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getMessageStats(WhatsAppAccount $account): array
    {
        return $this->messageRepo->countByBusinessId($account->business_id);
    }

    public function getDiagnostics(WhatsAppAccount $account): array
    {
        $token = $this->decryptToken($account->access_token);

        $info = null;
        $phoneNumbers = [];

        try {
            if ($account->waba_id) {
                $info = $this->apiClient->get($token, "/{$account->waba_id}", [
                    'fields' => 'id,name,currency,timezone_id',
                ]);
            }
        } catch (\Throwable $e) {
            $info = ['error' => $e->getMessage()];
        }

        try {
            if ($account->waba_id) {
                $phoneNumbers = $this->phoneNumberRepo->findByBusinessId($account->business_id);
            }
        } catch (\Throwable $e) {
            $phoneNumbers = [];
        }

        $messageStats = $this->getMessageStats($account);

        $tokenExpiresAt = $account->token_expires_at;
        $tokenDaysLeft = $tokenExpiresAt ? now()->diffInDays($tokenExpiresAt, false) : null;

        return [
            'connected' => $account->isConnected(),
            'account' => [
                'id' => $account->id,
                'uuid' => $account->uuid,
                'status' => $account->status?->value ?? $account->status,
                'waba_id' => $account->waba_id,
                'connected_at' => $account->connected_at?->toISOString(),
                'last_synced_at' => $account->last_synced_at?->toISOString(),
                'webhook_verified' => $account->webhook_verified_at !== null,
                'webhook_verified_at' => $account->webhook_verified_at?->toISOString(),
                'last_webhook_received_at' => $account->last_webhook_received_at?->toISOString(),
            ],
            'token' => [
                'status' => $account->token_status?->value ?? $account->token_status,
                'expires_at' => $tokenExpiresAt?->toISOString(),
                'days_left' => $tokenDaysLeft !== false && $tokenDaysLeft !== null ? (int) $tokenDaysLeft : null,
            ],
            'whatsapp_business_profile' => $info ? ($info['body'] ?? $info) : null,
            'phone_numbers' => $phoneNumbers,
            'message_stats' => $messageStats,
        ];
    }

    protected function encryptToken(?string $token): ?string
    {
        if ($token === null) {
            return null;
        }

        return config('whatsapp.token.encryption', true) ? Crypt::encryptString($token) : $token;
    }

    protected function decryptToken(?string $encryptedToken): ?string
    {
        if ($encryptedToken === null) {
            return null;
        }

        return config('whatsapp.token.encryption', true) ? Crypt::decryptString($encryptedToken) : $encryptedToken;
    }

    protected function generateWebhookSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }

    public function getDecryptedToken(WhatsAppAccount $account): ?string
    {
        return $this->decryptToken($account->access_token);
    }
}
