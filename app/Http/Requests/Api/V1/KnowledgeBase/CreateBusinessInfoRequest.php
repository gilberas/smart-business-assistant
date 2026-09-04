<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class CreateBusinessInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:hours,location,contact,social,policy,other'],
            'key' => ['required', 'string', 'max:100', 'unique:business_information,key,NULL,id,business_id,' . request()->user()->currentBusiness()?->id],
            'value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_public' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}