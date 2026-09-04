<?php

namespace App\DTOs\Broadcast;

class TemplateDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $language,
        public readonly string $category,
        public readonly string $body,
        public readonly ?array $header = null,
        public readonly ?string $footer = null,
        public readonly ?array $buttons = null,
        public readonly ?string $metaTemplateId = null,
        public readonly string $status = 'approved',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            language: $data['language'],
            category: $data['category'],
            body: $data['body'],
            header: $data['header'] ?? null,
            footer: $data['footer'] ?? null,
            buttons: $data['buttons'] ?? null,
            metaTemplateId: $data['meta_template_id'] ?? null,
            status: $data['status'] ?? 'approved',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->category,
            'body' => $this->body,
            'header' => $this->header,
            'footer' => $this->footer,
            'buttons' => $this->buttons,
            'meta_template_id' => $this->metaTemplateId,
            'status' => $this->status,
        ];
    }
}
