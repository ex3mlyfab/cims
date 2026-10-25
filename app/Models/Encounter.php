<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * CIMA-051: admission record / encounter. One active row per patient while
 * the patient is admitted to a ward/bed; the underlying bed occupancy is
 * represented by the matching `bed_allocations` ledger (CIMA-060).
 */
#[Fillable(['patient_id', 'ward_id', 'bed_id', 'team_id', 'treatment_status_id', 'arrived_at', 'discharged_at', 'notes'])]
class Encounter extends Model
{
    use HasUlids;

    protected $table = 'encounters';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'arrived_at' => 'datetime',
            'discharged_at' => 'datetime',
        ];
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('discharged_at');
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<Ward, $this> */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /** @return BelongsTo<Bed, $this> */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<TreatmentStatus, $this> */
    public function treatmentStatus(): BelongsTo
    {
        return $this->belongsTo(TreatmentStatus::class);
    }
}
