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
}
