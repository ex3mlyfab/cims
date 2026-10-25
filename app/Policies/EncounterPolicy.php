<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

/**
 * CIMA-051: admission-record authorization. Coarse checks live on the route
 * middleware; record-level create/update checks stay here.
 */
class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('encounters.manage');
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('encounters.manage');
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage') && $encounter->discharged_at === null;
    }

    public function discharge(User $user, Encounter $encounter): bool
    {
        return $user->can('encounters.manage') && $encounter->discharged_at === null;
    }
}
