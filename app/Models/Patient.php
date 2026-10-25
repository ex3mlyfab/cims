<?php

namespace App\Models;

use App\Enums\Sex;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * CIMA-050: patients — master patient registry. `patient_code` is the
 * human-facing identifier staff search on (public identifiers are never
 * ULIDs, ADR-001). Like beds/wards, the fullname is searched
 * case-insensitively via the default collation — no extra normalised
 * column. Encounters and allocations (ADR-003) are built on top of this
 * record later in Phase 5/6; a patient has many encounters.
 *
 * @property string $id
 * @property string $patient_code
 * @property string $fullname
 * @property Sex $sex
 * @property int $age
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['patient_code', 'fullname', 'sex', 'age'])]
class Patient extends Model
{
    use HasUlids;

    /** Validation bounds shared by the form request and UI hints (CIMA-050). */
    final public const CODE_MAX_LENGTH = 30;

    final public const FULLNAME_MAX_LENGTH = 120;

    final public const MAX_AGE = 130;

    protected $table = 'patients';

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'age' => 'integer',
        ];
    }

    /** @return Attribute<string, string> */
    protected function patientCode(): Attribute
    {
        return Attribute::make(
            set: static fn (string $value): string => mb_strtoupper(trim($value)),
        );
    }

    /**
     * Search-first lookup (CIMA-050): a term matches either the patient
     * code (prefix/exact) or the fullname (any position). Case folding is
     * handled by collation (MySQL CI / SQLite ASCII-ci).
     *
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%'.mb_strtolower($term).'%';

        $query->where(static function (Builder $q) use ($like): void {
            $q->where('patient_code', 'like', $like)
                ->orWhere('fullname', 'like', $like);
        });
    }
}
