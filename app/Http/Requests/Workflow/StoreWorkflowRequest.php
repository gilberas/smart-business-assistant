<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,active,paused,archived'],
            'execution_mode' => ['nullable', 'in:sequential,parallel'],
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['required_with:variables', 'string', 'max:255'],
            'variables.*.data_type' => ['nullable', 'in:string,number,boolean,array,date'],
            'variables.*.default_value' => ['nullable'],
            'variables.*.description' => ['nullable', 'string'],
            'variables.*.is_required' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'triggers' => ['nullable', 'array'],
            'triggers.*.type' => ['required_with:triggers', 'string'],
            'triggers.*.name' => ['nullable', 'string', 'max:255'],
            'triggers.*.payload' => ['nullable', 'array'],
            'triggers.*.is_enabled' => ['nullable', 'boolean'],
            'triggers.*.conditions' => ['nullable', 'array'],
            'triggers.*.conditions.*.field' => ['required_with:triggers.*.conditions', 'string'],
            'triggers.*.conditions.*.operator' => ['required_with:triggers.*.conditions', 'string'],
            'triggers.*.conditions.*.value' => ['nullable'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', 'string'],
            'conditions.*.operator' => ['required_with:conditions', 'string'],
            'conditions.*.value' => ['nullable'],
            'conditions.*.group_key' => ['nullable', 'string'],
            'conditions.*.boolean_connector' => ['nullable', 'in:and,or'],
            'conditions.*.priority' => ['nullable', 'integer', 'min:0'],
            'actions' => ['nullable', 'array'],
            'actions.*.type' => ['required_with:actions', 'string'],
            'actions.*.name' => ['nullable', 'string', 'max:255'],
            'actions.*.payload' => ['nullable', 'array'],
            'actions.*.position' => ['nullable', 'integer', 'min:0'],
            'actions.*.branch_key' => ['nullable', 'string'],
            'actions.*.is_enabled' => ['nullable', 'boolean'],
        ];
    }
}
