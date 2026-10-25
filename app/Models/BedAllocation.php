<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CIMA-060: active/closed bed allocation rows. Each occupied bed has one
 * active allocation; transfers close the old row instead of deleting it.
 *
 * @property string $id
 * @property string $bed_id
 * @property string $patient_id
 * @property string|null $actor_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string $reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['bed_id', 'patient_id', 'actor_id', 'started_at', 'ended_at', 'reason', 'notes'])]
class BedAllocation extends Model
{
    use HasUlids;

    protected $table = 'bed_allocations';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /** @param  Builder<self>  $query */
    public function scopeClosed(Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }

    /** @return BelongsTo<Bed, $this> */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
