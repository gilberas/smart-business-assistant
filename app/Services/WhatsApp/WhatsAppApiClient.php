<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\Message;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.api.base_url');
    }

    protected function mock(): ?MockWhatsAppService
    {
        return config('whatsapp.mock.enabled', false)
            ? new MockWhatsAppService
            : null;
    }

    public function withToken(string $token): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($token)
            ->timeout(config('whatsapp.api.timeout', 30))
            ->retry(
                config('whatsapp.api.max_retries', 3),
                100,
                fn ($exception) => $exception instanceof \Illuminate\Http\Client\RequestException
            )
            ->withHeaders(['Accept' => 'application/json']);
    }

    public function sendTextMessage(string $token, string $phoneNumberId, string $to, string $text, ?string $replyToId = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => false],
        ];

        if ($replyToId) {
            $payload['context'] = ['message_id' => $replyToId];
        }

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function sendMediaMessage(string $token, string $phoneNumberId, string $to, string $type, string $mediaIdOrUrl, ?string $caption = null, ?string $replyToId = null): array
    {
        $mediaKey = match ($type) {
            'image' => 'image',
            'audio' => 'audio',
            'video' => 'video',
            'document' => 'document',
            'sticker' => 'sticker',
            default => 'document',
        };

        $mediaPayload = [
            'id' => $mediaIdOrUrl,
        ];

        if ($caption && in_array($type, ['image', 'video', 'document'])) {
            $mediaPayload['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $mediaKey,
            $mediaKey => $mediaPayload,
        ];

        if ($replyToId) {
            $payload['context'] = ['message_id' => $replyToId];
        }

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function sendTemplateMessage(string $token, string $phoneNumberId, string $to, string $templateName, string $language, array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                    'policy' => 'deterministic',
                ],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function sendInteractiveButtons(string $token, string $phoneNumberId, string $to, string $bodyText, array $buttons): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => [
                    'buttons' => $buttons,
                ],
            ],
        ];

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function sendInteractiveList(string $token, string $phoneNumberId, string $to, string $headerText, string $bodyText, string $footerText, string $buttonText, array $sections): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => ['type' => 'text', 'text' => $headerText],
                'body' => ['text' => $bodyText],
                'footer' => ['text' => $footerText],
                'action' => [
                    'button' => $buttonText,
                    'sections' => $sections,
                ],
            ],
        ];

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function sendLocation(string $token, string $phoneNumberId, string $to, float $latitude, float $longitude, string $name = '', string $address = ''): array
    {
        $location = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if ($name) {
            $location['name'] = $name;
        }

        if ($address) {
            $location['address'] = $address;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'location',
            'location' => $location,
        ];

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function markAsRead(string $token, string $phoneNumberId, string $messageId): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        return $this->send($token, $phoneNumberId, $payload);
    }

    public function getBusinessProfile(string $token, string $phoneNumberId): array
    {
        $response = $this->withToken($token)
            ->get("/{$phoneNumberId}/whatsapp_business_profile", [
                'fields' => 'about,address,description,email,profile_picture_url,websites,vertical',
            ]);

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function getPhoneNumbers(string $token, string $wabaId): array
    {
        if ($mock = $this->mock()) {
            return $mock->getPhoneNumbers($token, $wabaId);
        }

        $response = $this->withToken($token)
            ->get("/{$wabaId}/phone_numbers", [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,pin_enabled,messaging_limit_tier',
            ]);

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function getMediaUrl(string $token, string $mediaId): array
    {
        $response = $this->withToken($token)
            ->get("/{$mediaId}");

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function downloadMedia(string $token, string $url): array
    {
        $response = Http::withToken($token)
            ->timeout(config('whatsapp.api.timeout', 30))
            ->get($url);

        return [
            'status_code' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
        ];
    }

    public function uploadMedia(string $token, string $phoneNumberId, string $filePath, string $mimeType): array
    {
        $response = $this->withToken($token)
            ->asMultipart()
            ->post("/{$phoneNumberId}/media", [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => basename($filePath),
                    'headers' => ['Content-Type' => $mimeType],
                ],
                [
                    'name' => 'messaging_product',
                    'contents' => 'whatsapp',
                ],
            ]);

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function send(string $token, string $phoneNumberId, array $payload): array
    {
        if ($mock = $this->mock()) {
            return $mock->sendMessage(new Message([
                'to_number' => $payload['to'] ?? '',
                'message_type' => $payload['type'] ?? 'text',
                'body' => $payload['text']['body'] ?? json_encode($payload),
            ]));
        }

        $response = $this->withToken($token)
            ->post("/{$phoneNumberId}/messages", $payload);

        $body = $response->json();

        if ($response->failed()) {
            Log::error('WhatsApp API request failed', [
                'phone_number_id' => $phoneNumberId,
                'status' => $response->status(),
                'response' => $body,
            ]);

            throw new \RuntimeException(
                $body['error']['message'] ?? 'WhatsApp API request failed',
                $response->status()
            );
        }

        return [
            'status_code' => $response->status(),
            'body' => $body,
        ];
    }

    public function get(string $token, string $endpoint, array $params = []): array
    {
        if ($mock = $this->mock()) {
            return $mock->get($token, $endpoint, $params);
        }

        $response = $this->withToken($token)
            ->get($endpoint, $params);

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }

    public function post(string $token, string $endpoint, array $payload = []): array
    {
        if ($mock = $this->mock()) {
            return $mock->post($token, $endpoint, $payload);
        }

        $response = $this->withToken($token)
            ->post($endpoint, $payload);

        return [
            'status_code' => $response->status(),
            'body' => $response->json(),
        ];
    }
}
