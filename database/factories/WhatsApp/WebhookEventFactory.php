<?php

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\WebhookEventStatus;
use App\Models\WhatsApp\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'event_type' => 'message_received',
            'meta_event_id' => (string) fake()->randomNumber(9),
            'status' => WebhookEventStatus::Pending,
            'payload' => [
                'object' => 'whatsapp_business_account',
                'entry' => [
                    [
                        'id' => (string) fake()->randomNumber(9),
                        'changes' => [
                            [
                                'value' => [
                                    'messaging_product' => 'whatsapp',
                                    'metadata' => [
                                        'display_phone_number' => '+15551234567',
                                        'phone_number_id' => 'mock_phone_id',
                                    ],
                                    'messages' => [
                                        [
                                            'from' => '+15559876543',
                                            'id' => 'wamid.' . Str::random(20),
                                            'timestamp' => (string) time(),
                                            'text' => ['body' => 'Test message'],
                                            'type' => 'text',
                                        ],
                                    ],
                                ],
                                'field' => 'messages',
                            ],
                        ],
                    ],
                ],
            ],
            'headers' => [
                'x-hub-signature-256' => ['sha256=' . hash_hmac('sha256', 'test', 'secret')],
                'content-type' => ['application/json'],
            ],
            'signature' => 'sha256=' . hash_hmac('sha256', 'test', 'secret'),
            'retry_count' => 0,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn () => [
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => WebhookEventStatus::Failed,
            'retry_count' => 3,
            'last_error' => 'API request failed: Connection timeout',
        ]);
    }

    public function deadLetter(): static
    {
        return $this->state(fn () => [
            'status' => WebhookEventStatus::DeadLetter,
            'retry_count' => 5,
            'last_error' => 'Max retries exceeded',
        ]);
    }

    public function statusUpdate(): static
    {
        return $this->state(fn () => [
            'event_type' => 'status_update',
            'payload' => [
                'entry' => [
                    [
                        'changes' => [
                            [
                                'value' => [
                                    'statuses' => [
                                        [
                                            'id' => 'wamid.' . Str::random(20),
                                            'status' => 'delivered',
                                            'timestamp' => (string) time(),
                                            'recipient_id' => '+15551234567',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
