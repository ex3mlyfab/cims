<?php

namespace App\Policies;

use App\Models\TreatmentStatus;
use App\Models\User;

/**
 * CIMA-052: treatment-status authorization (coarse gating lives with the
 * `permission:statuses.*` route middleware). Configuring the workflow
 * statuses is reserved for the ED manager and hospital administrator;
 * clinical roles may view the registry.
 */
class TreatmentStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('statuses.view');
    }

    public function view(User $user, TreatmentStatus $status): bool
    {
        return $user->can('statuses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('statuses.manage');
    }

    public function update(User $user, TreatmentStatus $status): bool
    {
        return $user->can('statuses.manage');
    }

    public function delete(User $user, TreatmentStatus $status): bool
    {
        return $user->can('statuses.manage');
    }
}
