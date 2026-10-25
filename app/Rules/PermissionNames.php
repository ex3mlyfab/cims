<?php

namespace App\Rules;

use App\Models\Permission;
use Illuminate\Contracts\Validation\InvokableRule;

/**
 * CIMA-041: validates permission names against the permission registry.
 * Unknown names would grant an illusion of access — reject them.
 */
class PermissionNames implements InvokableRule
{
    public function __invoke(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Invalid permission name.');
        } elseif (! Permission::query()->where('name', $value)->where('guard_name', 'web')->exists()) {
            $fail(__('Unknown permission: :name', ['name' => $value]));
        }
    }
}
