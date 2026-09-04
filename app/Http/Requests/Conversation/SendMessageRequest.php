<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required_without:media|string',
            'content_type' => 'nullable|string|max:20',
            'sender_type' => 'nullable|string|in:agent,system,ai,bot',
            'metadata' => 'nullable|array',
            'media' => 'nullable|array',
            'media.*.type' => 'required_with:media|string|max:30',
            'media.*.url' => 'required_with:media|string|url',
            'media.*.mime_type' => 'nullable|string|max:100',
            'media.*.file_name' => 'nullable|string|max:255',
        ];
    }
}
