<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * CIMA-046 / ADR-003: append-only bed history (bed_events). Writes are
 * always inserts; updates and deletes are refused at the model level, so
 * bed occupation history can never be rewritten. The FK to `beds` is
 * restrict-on-delete, so a bed with history cannot be hard-deleted either.
 *
 * @property string $id
 * @property string $bed_id
 * @property string|null $actor_id
 * @property string $action
 * @property array<string, mixed>|null $details
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['bed_id', 'actor_id', 'action', 'details', 'occurred_at'])]
class BedEvent extends Model
{
    use HasUlids;

    protected static function booted(): void
    {
        $refuse = static function (): void {
            throw new RuntimeException('bed_events is append-only (CIMA-046 / ADR-003).');
        };

        static::updating($refuse);
        static::deleting($refuse);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Bed, $this> */
    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
