<?php

namespace App\Policies;

use App\Models\User;

/**
 * CIMA-042: user record-level authorization. Coarse gating stays with the
 * `permission:users.*` route middleware; the business preconditions (last
 * super admin protection, self-deactivation) live in UserFormRequest and
 * UserService as typed domain failures.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('users.update');
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->can('users.deactivate');
    }

    public function resetPassword(User $actor, User $target): bool
    {
        return $actor->can('users.reset-password');
    }
}
