<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

/**
 * CIMA-051: admission-record authorization. Coarse checks live on the route
 * middleware; record-level create/update checks stay here. Ward scoping:
 * accounts other than super admin / hospital administrator may only work
 * with admissions inside wards they are assigned to.
 */
class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('encounters.manage');
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage') && $user->canAccessWard($encounter->ward_id);
    }

    public function create(User $user): bool
    {
        // Target ward is validated per request (EncounterFormRequest); the
        // route middleware already gates the capability.
        return $user->can('encounters.manage');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage')
            && $encounter->discharged_at === null
            && $user->canAccessWard($encounter->ward_id);
    }

    public function discharge(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage')
            && $encounter->discharged_at === null
            && $user->canAccessWard($encounter->ward_id);
    }
}
