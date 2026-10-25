<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * CIMA-043: teams — named clinical/operational teams with a lifecycle
 * status. Managed under the Facility Management group.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'status'])]
class Team extends Model
{
    use HasUlids;

    protected $table = 'teams';
}
