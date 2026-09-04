<?php

namespace App\DTOs;

class UpdateProfileDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly ?string $timezone,
        public readonly ?string $locale,
        public readonly ?array $preferences,
        public readonly mixed $avatar = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            phone: $data['phone'] ?? null,
            timezone: $data['timezone'] ?? null,
            locale: $data['locale'] ?? null,
            preferences: $data['preferences'] ?? null,
            avatar: $data['avatar'] ?? null,
        );
    }
}
