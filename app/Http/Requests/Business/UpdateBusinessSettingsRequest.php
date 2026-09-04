<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_hours' => ['sometimes', 'array'],
            'business_hours.monday' => ['sometimes', 'nullable', 'array'],
            'business_hours.tuesday' => ['sometimes', 'nullable', 'array'],
            'business_hours.wednesday' => ['sometimes', 'nullable', 'array'],
            'business_hours.thursday' => ['sometimes', 'nullable', 'array'],
            'business_hours.friday' => ['sometimes', 'nullable', 'array'],
            'business_hours.saturday' => ['sometimes', 'nullable', 'array'],
            'business_hours.sunday' => ['sometimes', 'nullable', 'array'],
            'ai_provider' => ['sometimes', 'string', 'max:20'],
            'ai_model' => ['sometimes', 'string', 'max:100'],
            'ai_temperature' => ['sometimes', 'numeric', 'min:0', 'max:2'],
            'ai_max_tokens' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'ai_system_prompt' => ['sometimes', 'nullable', 'string'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
