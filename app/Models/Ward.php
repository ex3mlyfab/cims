<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * CIMA-044: wards — physical areas holding beds (beds land in CIMA-045).
 * `capacity` is the declared bed capacity of the ward; the service refuses
 * to set it below the number of beds already installed.
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property int $capacity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name', 'capacity'])]
class Ward extends Model
{
    use HasUlids;

    protected $table = 'wards';

    /** Validation bounds shared by the form request and UI hints (CIMA-044). */
    final public const MAX_CAPACITY = 999;

    final public const CODE_MAX_LENGTH = 24;

    /** @return HasMany<Bed, $this> */
    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }
}
