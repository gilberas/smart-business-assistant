<?php

namespace App\Http\Requests\Broadcast;

use Illuminate\Foundation\Http\FormRequest;

class AudiencePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('broadcasts.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'filters' => ['required', 'array'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
