<?php

namespace Database\Factories\WhatsApp;

use App\Models\WhatsApp\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'media_id' => 'media.' . Str::random(10),
            'mime_type' => fake()->randomElement(['image/jpeg', 'image/png', 'audio/ogg', 'video/mp4', 'application/pdf']),
            'sha256' => hash('sha256', Str::random(40)),
            'file_size' => fake()->numberBetween(1024, 10 * 1024 * 1024),
            'file_name' => fake()->word() . '.' . fake()->fileExtension(),
            'local_path' => 'whatsapp/media/' . fake()->randomNumber() . '/' . Str::random(10),
            'disk' => 'local',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'mime_type' => 'image/jpeg',
            'file_name' => 'photo_' . Str::random(6) . '.jpg',
        ]);
    }
}
