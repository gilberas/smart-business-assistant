<?php

namespace App\DTOs\AI;

readonly class MessageDTO
{
    public function __construct(
        public string $role,
        public string $content,
        public ?string $name = null,
    ) {}

    public static function system(string $content): self
    {
        return new self(role: 'system', content: $content);
    }

    public static function user(string $content): self
    {
        return new self(role: 'user', content: $content);
    }

    public static function assistant(string $content): self
    {
        return new self(role: 'assistant', content: $content);
    }

    public function toArray(): array
    {
        $data = ['role' => $this->role, 'content' => $this->content];
        if ($this->name) {
            $data['name'] = $this->name;
        }
        return $data;
    }
}
