<?php

namespace App\Http\Requests\Team;

use App\Support\RoleRegistry;
use Illuminate\Foundation\Http\FormRequest;

class ChangeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:' . implode(',', RoleRegistry::tenantRoles())],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => __('team.invalid_role'),
        ];
    }
}
