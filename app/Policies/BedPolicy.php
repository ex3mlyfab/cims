<?php

namespace App\Policies;

use App\Models\Bed;
use App\Models\User;

/**
 * CIMA-045: bed authorization (coarse gating lives with the
 * `permission:beds.*` route middleware).
 */
class BedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('beds.view');
    }

    public function create(User $user): bool
    {
        return $user->can('beds.manage');
    }

    public function view(User $user, Bed $bed): bool
    {
        return $user->can('beds.view');
    }

    public function update(User $user, Bed $bed): bool
    {
        return $user->can('beds.manage');
    }

    public function delete(User $user, Bed $bed): bool
    {
        return $user->can('beds.manage');
    }
}
