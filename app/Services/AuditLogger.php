<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * CIMA-042 / ADR-004: the single writer of the append-only audit trail.
 * Call it inside the surrounding DB transaction of the mutating service;
 * it never deletes or contains secrets/PHI dumps — pass shape summaries.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $action,
        ?Model $record = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        /** @var User|null $actor */
        $actor = Auth::user();

        AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'record_type' => $record === null ? 'system' : $record::class,
            'record_id' => $record?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
