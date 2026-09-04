<?php

namespace Database\Factories;

use App\Enums\Conversation\ConversationPriority;
use App\Enums\Conversation\ConversationSource;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'source' => fake()->randomElement(ConversationSource::cases())->value,
            'channel' => 'whatsapp',
            'status' => 'new',
            'priority' => ConversationPriority::Normal,
            'handover_mode' => 'ai',
            'message_count' => 0,
            'last_message_at' => null,
        ];
    }

    public function withCustomer(): static
    {
        return $this->state(fn () => [
            'customer_id' => \App\Models\Customer::factory(),
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function priority(string $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'assigned_to' => \App\Models\User::factory(),
            'status' => 'assigned',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => 'archived']);
    }

    public function aiProcessing(): static
    {
        return $this->state(fn () => ['status' => 'ai_processing']);
    }
}
