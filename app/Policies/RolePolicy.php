<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * CIMA-041: role record-level authorization.
 *
 * Coarse gating is done by `permission:` route middleware; this policy adds
 * the record rules — most importantly protecting the seeded Super Admin role
 * (ADR-002): it can never be updated, renamed or deleted.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update') && ! $role->isSuperAdmin();
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! $role->isSuperAdmin();
    }
}
