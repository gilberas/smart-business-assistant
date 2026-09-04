<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class StoreAIProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:50', 'unique:ai_providers,slug'],
            'base_url' => ['nullable', 'string', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
