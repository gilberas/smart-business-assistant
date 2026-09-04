<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAIConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['string', 'in:openai,anthropic,google,openrouter'],
            'model' => ['string', 'max:100'],
            'temperature' => ['numeric', 'min:0', 'max:2'],
            'top_p' => ['numeric', 'min:0', 'max:1'],
            'max_tokens' => ['integer', 'min:1', 'max:1000000'],
            'timeout_seconds' => ['integer', 'min:1', 'max:300'],
            'streaming' => ['boolean'],
            'language' => ['string', 'max:10'],
            'safety_settings' => ['nullable', 'array'],
            'system_prompt' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
