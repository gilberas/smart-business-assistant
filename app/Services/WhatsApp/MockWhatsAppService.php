<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\Message;
use Illuminate\Support\Facades\Log;

class MockWhatsAppService
{
    public function isEnabled(): bool
    {
        return config('whatsapp.mock.enabled', false);
    }

    public function sendMessage(Message $message): array
    {
        Log::info('[Mock WhatsApp] Sending message', [
            'to' => $message->to_number,
            'type' => $message->message_type,
            'body' => $message->body,
        ]);

        $fakeId = 'wamid.mock.' . bin2hex(random_bytes(8));

        return [
            'status_code' => 201,
            'body' => [
                'messaging_product' => 'whatsapp',
                'contacts' => [
                    [
                        'input' => $message->to_number,
                        'wa_id' => $message->to_number,
                    ],
                ],
                'messages' => [
                    [
                        'id' => $fakeId,
                    ],
                ],
            ],
        ];
    }

    public function getPhoneNumbers(string $token, string $wabaId): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'data' => [
                    [
                        'id' => '123456789',
                        'display_phone_number' => '+15551234567',
                        'phone_number' => '+15551234567',
                        'verified_name' => 'Test Business',
                        'quality_rating' => 'GREEN',
                        'code_verification_status' => 'VERIFIED',
                        'pin_enabled' => false,
                        'messaging_limit_tier' => 'TIER_250',
                    ],
                ],
            ],
        ];
    }

    public function getBusinessProfile(string $token, string $phoneNumberId): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'data' => [
                    [
                        'about' => 'Test business for development',
                        'description' => 'A mock WhatsApp Business profile',
                        'email' => 'test@example.com',
                    ],
                ],
            ],
        ];
    }

    public function getMediaUrl(string $token, string $mediaId): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'url' => 'https://mock.whatsapp.example.com/media/' . $mediaId,
                'mime_type' => 'image/png',
                'sha256' => hash('sha256', $mediaId),
                'file_size' => 1024,
                'filename' => 'mock_image.png',
                'expiry' => now()->addDay()->timestamp,
            ],
        ];
    }

    public function downloadMedia(string $token, string $url): array
    {
        $dummyContent = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk'
        );

        return [
            'status_code' => 200,
            'body' => $dummyContent,
            'headers' => ['Content-Type' => 'image/png'],
        ];
    }

    public function uploadMedia(string $token, string $phoneNumberId, string $filePath, string $mimeType): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'id' => 'mock.media.' . bin2hex(random_bytes(8)),
            ],
        ];
    }

    public function get(string $token, string $endpoint, array $params = []): array
    {
        return [
            'status_code' => 200,
            'body' => [
                'id' => 'mock_waba_id',
                'name' => 'Mock WABA',
                'currency' => 'USD',
                'timezone_id' => 'America/New_York',
            ],
        ];
    }

    public function post(string $token, string $endpoint, array $payload = []): array
    {
        return ['status_code' => 200, 'body' => ['success' => true]];
    }

    public function getFakeWebhookPayload(string $type = 'text'): array
    {
        $base = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'mock_waba_id',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '+15551234567',
                                    'phone_number_id' => 'mock_phone_id',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Test User'],
                                        'wa_id' => '+15559876543',
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $messages = match ($type) {
            'text' => [
                [
                    'from' => '+15559876543',
                    'id' => 'wamid.mock.' . bin2hex(random_bytes(8)),
                    'timestamp' => (string) time(),
                    'text' => ['body' => 'Hello from mock!'],
                    'type' => 'text',
                ],
            ],
            'image' => [
                [
                    'from' => '+15559876543',
                    'id' => 'wamid.mock.' . bin2hex(random_bytes(8)),
                    'timestamp' => (string) time(),
                    'image' => [
                        'id' => 'mock_media_id_' . bin2hex(random_bytes(4)),
                        'mime_type' => 'image/jpeg',
                    ],
                    'type' => 'image',
                ],
            ],
            'interactive' => [
                [
                    'from' => '+15559876543',
                    'id' => 'wamid.mock.' . bin2hex(random_bytes(8)),
                    'timestamp' => (string) time(),
                    'interactive' => [
                        'type' => 'button_reply',
                        'button_reply' => [
                            'id' => 'btn_1',
                            'title' => 'Option 1',
                        ],
                    ],
                    'type' => 'interactive',
                ],
            ],
            'status' => [
                [
                    'id' => 'wamid.mock.status',
                    'status' => 'delivered',
                    'timestamp' => (string) time(),
                    'recipient_id' => '+15551234567',
                ],
            ],
            default => [
                [
                    'from' => '+15559876543',
                    'id' => 'wamid.mock.' . bin2hex(random_bytes(8)),
                    'timestamp' => (string) time(),
                    'text' => ['body' => 'Hello from mock!'],
                    'type' => 'text',
                ],
            ],
        };

        $base['entry'][0]['changes'][0]['value']['messages'] = $messages;

        return $base;
    }
}
