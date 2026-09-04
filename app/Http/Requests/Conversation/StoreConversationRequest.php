<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'title' => 'nullable|string|max:255',
            'source' => 'nullable|string|in:whatsapp,telegram,messenger,instagram,sms,email,web,api,internal',
            'channel' => 'nullable|string|max:30',
            'channel_id' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'metadata' => 'nullable|array',
        ];
    }
}
