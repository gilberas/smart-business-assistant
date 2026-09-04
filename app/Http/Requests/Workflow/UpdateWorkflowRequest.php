<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['sometimes', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,active,paused,archived'],
            'execution_mode' => ['nullable', 'in:sequential,parallel'],
            'variables' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
