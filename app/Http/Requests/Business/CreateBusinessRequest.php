<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('businesses', 'slug')],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:64', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:8'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'industry' => ['sometimes', 'nullable', 'string', 'max:100'],
            'employee_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            'onboarding_step' => ['sometimes', 'nullable', 'in:business-info,details,preferences'],
        ];
    }
}
