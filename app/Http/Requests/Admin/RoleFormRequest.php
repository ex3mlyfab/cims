<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Rules\PermissionNames;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-041: shared validation for role create/update.
 */
class RoleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware grants page access; the policy decides per record.
        return (bool) $this->user();
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Role|null $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:30',
                'lowercase',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore(is_object($role) ? $role->id : null),
                Rule::notIn([Role::SUPER_ADMIN]),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => [new PermissionNames],
        ];
    }

    public function messages(): array
    {
        return [
            'name.not_in' => 'The super admin role is reserved.',
            'name.unique' => 'A role with that name already exists.',
            'name.lowercase' => 'Role names must be lowercase.',
        ];
    }
}
