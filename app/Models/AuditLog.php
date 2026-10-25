<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * CIMA-042 / ADR-004: append-only audit log. Writes are always inserts;
 * updates and deletes are refused at the model level.
 */
#[Fillable(['actor_id', 'action', 'record_type', 'record_id', 'before', 'after', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    use HasUlids;

    protected static function booted(): void
    {
        $refuse = static function (): void {
            throw new RuntimeException('audit_logs is append-only (ADR-004).');
        };

        static::updating($refuse);
        static::deleting($refuse);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
