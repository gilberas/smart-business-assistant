<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $infoId = $this->route('info')?->id ?? 'NULL';

        return [
            'type' => ['sometimes', 'required', 'string', 'in:hours,location,contact,social,policy,other'],
            'key' => ['sometimes', 'required', 'string', 'max:100', 'unique:business_information,key,' . $infoId . ',id,business_id,' . request()->user()->currentBusiness()?->id],
            'value' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'is_public' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}