<?php

namespace App\Models;

use App\Enums\BedStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * CIMA-045: beds — physical bed units inside a ward. The `number` is unique
 * per ward; `status` follows the bed state machine (BedStatus), where
 * `occupied` is only ever entered through the allocation workflow (CIMA-060)
 * so an occupied bed always has an active allocation row.
 *
 * @property string $id
 * @property string $ward_id
 * @property string $number
 * @property BedStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ward_id', 'number', 'status'])]
class Bed extends Model
{
    use HasUlids;

    protected $table = 'beds';

    /** Validation bounds shared by the form request and UI hints (CIMA-045). */
    final public const NUMBER_MAX_LENGTH = 24;

    protected function casts(): array
    {
        return [
            'status' => BedStatus::class,
        ];
    }

    /** @return BelongsTo<Ward, $this> */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /** @return HasMany<BedEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(BedEvent::class);
    }

    /** @return HasMany<BedAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(BedAllocation::class);
    }

    /** @return HasOne<BedAllocation, $this> */
    public function activeAllocation(): HasOne
    {
        return $this->hasOne(BedAllocation::class)->whereNull('ended_at');
    }
}
