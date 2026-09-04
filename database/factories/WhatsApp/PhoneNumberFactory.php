<?php

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsApp\PhoneNumberStatus;
use App\Models\WhatsApp\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PhoneNumberFactory extends Factory
{
    protected $model = PhoneNumber::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'phone_number' => '+1' . fake()->numerify('##########'),
            'phone_number_id' => (string) fake()->randomNumber(9),
            'display_phone' => '+1 ' . fake()->numerify('(###) ###-####'),
            'verified_name' => fake()->company(),
            'quality_rating' => fake()->randomElement(['GREEN', 'YELLOW', 'RED']),
            'status' => PhoneNumberStatus::Active,
            'code_verification_status' => 'VERIFIED',
            'pin_enabled' => false,
            'messaging_limit_tier' => 'TIER_250',
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn () => [
            'status' => PhoneNumberStatus::Flagged,
            'quality_rating' => 'YELLOW',
        ]);
    }
}
