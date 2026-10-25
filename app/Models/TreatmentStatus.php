<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * CIMA-052: treatment_statuses — the hospital's configurable treatment
 * workflow statuses (the treatment axis of the four status axes). Ordered
 * by `sort_order`; inactive rows are hidden from the clinical forms but
 * stay valid for encounters already referencing them (deletion while in
 * use is refused once CIMA-060's encounters arrive, Schema-guarded).
 *
 * @property string $id
 * @property string $slug
 * @property string $label
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'label', 'sort_order', 'is_active'])]
class TreatmentStatus extends Model
{
    use HasUlids;

    /** Validation bounds shared by the form request and UI hints (CIMA-052). */
    final public const SLUG_MAX_LENGTH = 30;

    final public const LABEL_MAX_LENGTH = 60;

    final public const MAX_SORT_ORDER = 99;

    protected $table = 'treatment_statuses';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Active statuses in display order — the picker options for the
     * admission create/update flows (CIMA-051/061) and the projections
     * that render status labels.
     *
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('label');
    }
}
