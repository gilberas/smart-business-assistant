<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $businessId = $this->route('business')?->id ?? $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('businesses', 'slug')->ignore($businessId)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'timezone' => ['sometimes', 'string', 'max:64', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:8'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'employee_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            'branding' => ['sometimes', 'array'],
            'preferences' => ['sometimes', 'array'],
        ];
    }
}
