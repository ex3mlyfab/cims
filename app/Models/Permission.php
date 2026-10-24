<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Package model adaptation for CIMA ULID primary keys (ADR-001).
 */
class Permission extends SpatiePermission
{
    use HasUlids;
}
