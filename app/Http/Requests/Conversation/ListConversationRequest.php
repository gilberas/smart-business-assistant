<?php

namespace App\Http\Requests\Conversation;

use Illuminate\Foundation\Http\FormRequest;

class ListConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|string|in:new,waiting,assigned,ai_processing,human_reply,resolved,closed,archived',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'channel' => 'nullable|string|max:30',
            'source' => 'nullable|string|max:30',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'unassigned' => 'nullable|boolean',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'label_id' => 'nullable|integer|exists:conversation_labels,id',
            'active' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'sort' => 'nullable|string|in:created_at,updated_at,last_message_at,priority,status',
            'direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
