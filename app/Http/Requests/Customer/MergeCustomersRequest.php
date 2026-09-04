<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class MergeCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_id' => ['required', 'integer', 'exists:customers,id'],
            'duplicate_id' => ['required', 'integer', 'exists:customers,id', 'different:primary_id'],
        ];
    }
}
