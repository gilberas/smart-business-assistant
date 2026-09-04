<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ImportCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:5000'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.phone' => ['nullable', 'string', 'max:30'],
            'rows.*.phone_alt' => ['nullable', 'string', 'max:30'],
            'rows.*.email' => ['nullable', 'email', 'max:255'],
            'rows.*.company_name' => ['nullable', 'string', 'max:255'],
            'rows.*.job_title' => ['nullable', 'string', 'max:255'],
            'rows.*.tags' => ['nullable'],
            'rows.*.lifecycle_stage' => ['nullable', 'string', 'max:30'],
            'rows.*.customer_type' => ['nullable', 'string', 'max:20'],
            'rows.*.timezone' => ['nullable', 'string', 'max:50'],
            'rows.*.preferred_language' => ['nullable', 'string', 'max:10'],
            'update_duplicates' => ['nullable', 'boolean'],
        ];
    }
}
