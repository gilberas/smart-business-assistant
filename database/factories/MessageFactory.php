<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'sender_type' => fake()->randomElement(['customer', 'ai', 'agent']),
            'content' => fake()->paragraph(),
            'content_type' => 'text',
            'status' => 'sent',
        ];
    }
}
