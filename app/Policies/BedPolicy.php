<?php

namespace App\Policies;

use App\Models\Bed;
use App\Models\User;

/**
 * CIMA-045: bed authorization (coarse gating lives with the
 * `permission:beds.*` route middleware). Record rules respect the ward
 * scoping: accounts other than super admin / hospital administrator may
 * only view/manage beds inside their assigned wards.
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
        return $user->can('beds.view') && $user->canAccessWard($bed->ward_id);
    }

    public function update(User $user, Bed $bed): bool
    {
        return $user->can('beds.manage') && $user->canAccessWard($bed->ward_id);
    }

    public function delete(User $user, Bed $bed): bool
    {
        return $user->can('beds.manage') && $user->canAccessWard($bed->ward_id);
    }
}
