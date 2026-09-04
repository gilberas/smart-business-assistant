<?php

namespace App\Http\Requests\Broadcast;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('whatsapp.templates.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:10'],
            'category' => ['required', 'string', Rule::in(['MARKETING', 'UTILITY', 'AUTHENTICATION'])],
            'body' => ['required', 'string', 'max:4096'],
            'header' => ['nullable', 'array'],
            'header.type' => ['required_with:header', 'string', Rule::in(['TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'])],
            'header.text' => ['required_if:header.type,TEXT', 'nullable', 'string', 'max:200'],
            'footer' => ['nullable', 'string', 'max:200'],
            'buttons' => ['nullable', 'array'],
        ];
    }
}
