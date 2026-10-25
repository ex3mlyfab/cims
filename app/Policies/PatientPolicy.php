<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

/**
 * CIMA-050: patient authorization (coarse gating lives with the
 * `permission:patients.*` route middleware). Records officers, nurses and
 * ED managers register patients; every clinical role can search and view.
 */
class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patients.view');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('patients.create');
    }
}
