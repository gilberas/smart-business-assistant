<?php

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\MessageDirection;
use App\Enums\WhatsApp\MessageStatus;
use App\Enums\WhatsApp\MessageType;
use App\Models\WhatsApp\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'from_number' => '+1' . fake()->numerify('##########'),
            'to_number' => '+1' . fake()->numerify('##########'),
            'direction' => MessageDirection::Inbound,
            'message_type' => MessageType::Text,
            'meta_message_id' => 'wamid.' . Str::random(20),
            'status' => MessageStatus::Delivered,
            'body' => fake()->sentence(),
            'sender_type' => 'customer',
            'content' => fake()->sentence(),
            'content_type' => 'text',
            'sent_at' => now(),
            'delivered_at' => now(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn () => [
            'direction' => MessageDirection::Inbound,
        ]);
    }

    public function outbound(): static
    {
        return $this->state(fn () => [
            'direction' => MessageDirection::Outbound,
            'status' => MessageStatus::Sent,
        ]);
    }

    public function text(): static
    {
        return $this->state(fn () => [
            'message_type' => MessageType::Text,
            'body' => fake()->sentence(),
        ]);
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'message_type' => MessageType::Image,
            'media_id' => 'media.' . Str::random(10),
            'caption' => fake()->sentence(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => MessageStatus::Failed,
            'failed_at' => now(),
            'errors' => ['code' => 131026, 'message' => 'Message failed to send'],
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => MessageStatus::Pending,
            'meta_message_id' => null,
            'sent_at' => null,
        ]);
    }
}
