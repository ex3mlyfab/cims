<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Package model adaptation for CIMA ULID primary keys (ADR-001).
 */
class Role extends SpatieRole
{
    use HasUlids;

    public const SUPER_ADMIN = 'super admin';

    /**
     * Whether this role is the seeded Super Admin, which must be protected
     * from rename/delete and permission changes (ADR-002).
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }
}
