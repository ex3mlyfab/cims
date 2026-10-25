<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * CIMA-042: shared validation for user create/update. Roles are registry
 * names; the `super admin` role may only be granted/kept by a super admin.
 */
class UserFormRequest extends FormRequest
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
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc,spoof',
                'lowercase',
                'max:255',
                Rule::unique('users', 'email')->ignore(is_object($user) ? $user->id : null),
            ],
            'roles' => ['present', 'array', 'max:5'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roleNames = (array) $this->input('roles', []);

            if (
                in_array(Role::SUPER_ADMIN, $roleNames, true)
                && ! $this->user()?->hasRole(Role::SUPER_ADMIN)
            ) {
                $validator->errors()->add('roles.0', __('Only a super admin can grant or keep the super admin role.'));
            }
        });
    }
}
