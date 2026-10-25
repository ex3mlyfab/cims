<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Ward;

/**
 * CIMA-044: ward authorization (coarse gating lives with the
 * `permission:wards.*` route middleware).
 */
class WardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('wards.view');
    }

    public function create(User $user): bool
    {
        return $user->can('wards.manage');
    }

    public function update(User $user, Ward $ward): bool
    {
        return $user->can('wards.manage');
    }

    public function delete(User $user, Ward $ward): bool
    {
        return $user->can('wards.manage');
    }
}
