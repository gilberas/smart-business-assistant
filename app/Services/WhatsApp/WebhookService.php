<?php

namespace App\Services\WhatsApp;

use App\Events\WhatsApp\WebhookReceived;
use App\Jobs\WhatsApp\ProcessWebhookEvent;
use App\Models\Business;
use App\Models\WhatsApp\WebhookEvent;
use App\Repositories\WhatsApp\WebhookEventRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function __construct(
        private readonly WebhookEventRepository $webhookEventRepo,
        private readonly WhatsAppAccountRepository $accountRepo,
    ) {}

    public function verify(Request $request, string $businessUuid): array
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe' || !$token) {
            return ['success' => false, 'status' => 400];
        }

        $business = Business::where('uuid', $businessUuid)->first();
        if (!$business) {
            return ['success' => false, 'status' => 404];
        }

        $account = $this->accountRepo->findByBusinessId($business->id);
        if (!$account) {
            return ['success' => false, 'status' => 404];
        }

        $expectedToken = config('whatsapp.webhook.verify_token');

        if ($token !== $expectedToken) {
            Log::warning('Webhook verification token mismatch', [
                'business_uuid' => $businessUuid,
                'provided_token' => substr($token, 0, 10) . '...',
            ]);
            return ['success' => false, 'status' => 403];
        }

        $this->accountRepo->updateWebhookSecret($account, $token);

        Log::info('Webhook verified successfully', [
            'business_uuid' => $businessUuid,
            'account_id' => $account->id,
        ]);

        return ['success' => true, 'challenge' => (int) $challenge, 'status' => 200];
    }

    public function handleIncoming(Request $request, string $businessUuid): void
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        if ($this->isTestNotification($payload)) {
            Log::info('Received WhatsApp test notification', [
                'business_uuid' => $businessUuid,
            ]);
            return;
        }

        $signature = $request->header(config('whatsapp.webhook.signature_header', 'x-hub-signature-256'));

        $business = Business::where('uuid', $businessUuid)->first();
        if (!$business) {
            Log::warning('Webhook received for unknown business', ['business_uuid' => $businessUuid]);
            return;
        }

        $account = $this->accountRepo->findByBusinessId($business->id);

        if ($account) {
            $this->accountRepo->update($account, [
                'last_webhook_received_at' => now(),
            ]);
        }

        $webhookEvent = $this->webhookEventRepo->create([
            'business_id' => $business->id,
            'whatsapp_account_id' => $account?->id,
            'event_type' => $this->detectEventType($payload),
            'meta_event_id' => $this->extractMetaEventId($payload),
            'phone_number_id' => $this->extractPhoneNumberId($payload),
            'payload' => $payload,
            'headers' => $headers,
            'signature' => $signature,
            'status' => 'pending',
        ]);

        WebhookReceived::dispatch($payload, $headers, $account);

        ProcessWebhookEvent::dispatch($webhookEvent);
    }

    public function validateSignature(Request $request, ?string $appSecret): bool
    {
        if (!$appSecret) {
            return true;
        }

        $signature = $request->header(config('whatsapp.webhook.signature_header', 'x-hub-signature-256'));
        if (!$signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }

    public function getHealth(int $businessId): array
    {
        $account = $this->accountRepo->findByBusinessId($businessId);

        if (!$account) {
            return [
                'connected' => false,
                'webhook_verified' => false,
                'last_received' => null,
                'pending_events' => 0,
                'failed_events' => 0,
                'dead_letter_events' => 0,
            ];
        }

        return [
            'connected' => $account->isConnected(),
            'webhook_verified' => $account->webhook_verified_at !== null,
            'webhook_verified_at' => $account->webhook_verified_at?->toISOString(),
            'last_received' => $account->last_webhook_received_at?->toISOString(),
            'pending_events' => $this->webhookEventRepo->countByBusinessAndStatus($businessId, 'pending'),
            'failed_events' => $this->webhookEventRepo->countByBusinessAndStatus($businessId, 'failed'),
            'dead_letter_events' => $this->webhookEventRepo->countByBusinessAndStatus($businessId, 'dead_letter'),
        ];
    }

    protected function isTestNotification(array $payload): bool
    {
        return ($payload['entry'][0]['id'] ?? null) === '0' ||
            ($payload['entry'][0]['changes'][0]['value']['messaging_product'] ?? '') === 'test';
    }

    protected function detectEventType(array $payload): string
    {
        $entry = $payload['entry'][0] ?? [];
        $change = $entry['changes'][0] ?? [];
        $value = $change['value'] ?? [];
        $field = $change['field'] ?? 'unknown';

        if (!empty($value['messages'])) {
            return 'message_received';
        }

        if (!empty($value['statuses'])) {
            return 'status_update';
        }

        if ($field === 'message_template_status_update') {
            return 'template_update';
        }

        return $field;
    }

    protected function extractMetaEventId(array $payload): ?string
    {
        return $payload['entry'][0]['id'] ?? null;
    }

    protected function extractPhoneNumberId(array $payload): ?string
    {
        return $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
    }
}
