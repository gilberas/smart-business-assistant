<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'access_token' => ['nullable', 'string'],
            'waba_id' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string'],
        ];
    }
}
