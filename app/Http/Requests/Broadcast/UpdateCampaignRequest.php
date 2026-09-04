<?php

namespace App\Http\Requests\Broadcast;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('broadcasts.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:whatsapp_templates,id'],
            'message_content' => ['nullable', 'string', 'max:4096'],
            'recipient_filter' => ['sometimes', 'required', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
