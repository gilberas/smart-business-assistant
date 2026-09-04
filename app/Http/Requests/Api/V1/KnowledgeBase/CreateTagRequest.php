<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class CreateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:knowledge_tags,name,NULL,id,business_id,' . request()->user()->currentBusiness()?->id],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}