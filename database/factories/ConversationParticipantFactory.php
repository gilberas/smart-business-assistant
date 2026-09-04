<?php

namespace Database\Factories;

use App\Enums\Conversation\ParticipantRole;
use App\Models\ConversationParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationParticipantFactory extends Factory
{
    protected $model = ConversationParticipant::class;

    public function definition(): array
    {
        return [
            'role' => ParticipantRole::Agent,
            'joined_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn () => ['role' => ParticipantRole::Owner]);
    }

    public function observer(): static
    {
        return $this->state(fn () => ['role' => ParticipantRole::Observer]);
    }
}
