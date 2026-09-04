<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromptTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'type' => ['string', 'in:system,business,conversation,knowledge,classification,moderation,summarization'],
            'content' => ['string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'changelog' => ['nullable', 'string', 'max:500'],
        ];
    }
}
