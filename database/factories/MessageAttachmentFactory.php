<?php

namespace Database\Factories;

use App\Models\MessageAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageAttachmentFactory extends Factory
{
    protected $model = MessageAttachment::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['image', 'document', 'audio', 'video']),
            'url' => fake()->url(),
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'application/pdf', 'audio/ogg']),
            'file_name' => fake()->word() . '.' . fake()->fileExtension(),
            'file_size' => fake()->numberBetween(1024, 5242880),
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'width' => fake()->numberBetween(200, 1920),
            'height' => fake()->numberBetween(200, 1080),
        ]);
    }

    public function document(): static
    {
        return $this->state(fn () => [
            'type' => 'document',
            'mime_type' => 'application/pdf',
        ]);
    }
}
