<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * CIMA-042: shared validation for user create/update. Roles are registry
 * names; the `super admin` role may only be granted/kept by a super admin.
 *
 * Usernames are case-insensitive: input is lowercased before validation and
 * persisted in lowercase. Emails are optional; when no email is supplied a
 * password must be set (otherwise the account would be unusable).
 */
class UserFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware grants page access; the policy decides per record.
        return (bool) $this->user();
    }

    /**
     * Normalise the username so uppercase input is accepted on create/edit.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => Str::lower(trim((string) $this->input('username'))),
            ]);
        }

        if ($this->has('email')) {
            $email = trim((string) $this->input('email'));

            $this->merge(['email' => $email === '' ? null : Str::lower($email)]);
        }
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        $passwordRules = ['nullable', 'string', 'confirmed', Password::default()];

        // On create, either a password or an email (for the set-password
        // link) must be supplied.
        if ($user === null) {
            $passwordRules[] = 'required_without:email';
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore(is_object($user) ? $user->id : null),
            ],
            'email' => [
                'nullable',
                'string',
                'email:rfc,spoof',
                'max:255',
                Rule::unique('users', 'email')->ignore(is_object($user) ? $user->id : null),
            ],
            'password' => $passwordRules,
            'roles' => ['present', 'array', 'max:5'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'wards' => ['present', 'array'],
            'wards.*' => ['string', Rule::exists('wards', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => __('The username may only contain letters, numbers, dots, dashes and underscores.'),
            'password.required_without' => __('Enter a password or an email address so the user can access their account.'),
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
