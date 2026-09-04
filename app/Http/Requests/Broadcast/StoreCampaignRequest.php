<?php

namespace App\Http\Requests\Broadcast;

use App\Enums\Broadcast\BroadcastCampaignStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('broadcasts.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'template_id' => ['nullable', 'integer', 'exists:whatsapp_templates,id'],
            'message_content' => ['nullable', 'string', 'max:4096'],
            'recipient_filter' => ['required', 'array'],
            'recipient_filter.*' => ['required'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'status' => ['nullable', Rule::in(['draft', 'scheduled'])],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
