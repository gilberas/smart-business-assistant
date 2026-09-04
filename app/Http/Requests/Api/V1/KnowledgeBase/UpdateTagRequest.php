<?php

namespace App\Http\Requests\Api\V1\KnowledgeBase;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('tag')?->id ?? 'NULL';

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', 'unique:knowledge_tags,name,' . $tagId . ',id,business_id,' . request()->user()->currentBusiness()?->id],
            'color' => ['nullable', 'string', 'max:20'],
        ];
    }
}