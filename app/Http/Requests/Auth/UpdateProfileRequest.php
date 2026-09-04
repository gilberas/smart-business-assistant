<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'timezone' => ['sometimes', 'string', 'max:64', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:8'],
            'avatar' => ['sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'preferences' => ['sometimes', 'array'],
        ];
    }
}
