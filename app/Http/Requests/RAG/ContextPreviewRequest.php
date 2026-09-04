<?php

namespace App\Http\Requests\RAG;

use Illuminate\Foundation\Http\FormRequest;

class ContextPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:1000'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'include_prompt' => ['nullable', 'boolean'],
            'user_message' => ['required_with:include_prompt', 'string', 'max:5000'],
        ];
    }
}
