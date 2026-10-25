<?php

namespace App\Services;

use App\Enums\BedStatus;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\BedEvent;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-060: bed allocation workflow core. Only this service may place a bed
 * into the occupied state, close an active allocation, or transfer patients
 * between beds. The guard is transaction-safe and the bed history log is
 * updated in the same unit of work.
 */
class BedAllocationService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, Bed $bed, Patient $patient, array $data = []): BedAllocation
    {
        return DB::transaction(function () use ($actor, $bed, $patient, $data): BedAllocation {
            $lockedBed = Bed::query()->whereKey($bed->getKey())->lockForUpdate()->firstOrFail();
            $this->assertBedCanBeAllocated($lockedBed);

            $allocation = new BedAllocation;
            $allocation->bed_id = $lockedBed->id;
            $allocation->patient_id = $patient->id;
            $allocation->actor_id = $actor->id;
            $allocation->started_at = $data['started_at'] ?? now();
            $allocation->reason = (string) ($data['reason'] ?? 'admission');
            $allocation->notes = array_key_exists('notes', $data) ? $data['notes'] : null;
            $allocation->save();

            $lockedBed->status = BedStatus::Occupied;
            $lockedBed->save();

            $this->auditLogger->record('bed.allocation.created', $allocation, after: $this->summary($allocation));
            $this->recordEvent($actor, $lockedBed, 'bed.allocated', [
                'allocation_id' => $allocation->id,
                'patient_id' => $patient->id,
                'reason' => $allocation->reason,
            ]);

            return $allocation;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(User $actor, Bed $bed, Patient $patient, array $data = []): BedAllocation
    {
        return $this->create($actor, $bed, $patient, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function allocate(User $actor, Bed $bed, Patient $patient, array $data = []): BedAllocation
    {
        return $this->create($actor, $bed, $patient, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(User $actor, BedAllocation $allocation, array $data = []): BedAllocation
    {
        return DB::transaction(function () use ($actor, $allocation, $data): BedAllocation {
            $lockedAllocation = BedAllocation::query()->whereKey($allocation->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedAllocation->ended_at !== null) {
                throw new BedInUse(__('This allocation has already been closed.'));
            }

            $before = $this->summary($lockedAllocation);

            $lockedAllocation->ended_at = $data['ended_at'] ?? now();
            $lockedAllocation->reason = (string) ($data['reason'] ?? $lockedAllocation->reason);
            $lockedAllocation->notes = array_key_exists('notes', $data) ? $data['notes'] : $lockedAllocation->notes;
            $lockedAllocation->save();

            $bed = Bed::query()->whereKey($lockedAllocation->bed_id)->lockForUpdate()->firstOrFail();
            $bed->status = BedStatus::Cleaning;
            $bed->save();

            $this->auditLogger->record('bed.allocation.closed', $lockedAllocation, before: $before, after: $this->summary($lockedAllocation));
            $this->recordEvent($actor, $bed, 'bed.allocation_closed', [
                'allocation_id' => $lockedAllocation->id,
                'patient_id' => $lockedAllocation->patient_id,
                'reason' => $lockedAllocation->reason,
            ]);

            return $lockedAllocation;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function end(User $actor, BedAllocation $allocation, array $data = []): BedAllocation
    {
        return $this->close($actor, $allocation, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function transfer(User $actor, BedAllocation $allocation, Bed $targetBed, Patient $patient, array $data = []): BedAllocation
    {
        return DB::transaction(function () use ($actor, $allocation, $targetBed, $patient, $data): BedAllocation {
            if ($allocation->bed_id === $targetBed->id) {
                $allocation->reason = (string) ($data['reason'] ?? $allocation->reason);
                $allocation->notes = array_key_exists('notes', $data) ? $data['notes'] : $allocation->notes;
                $allocation->save();

                return $allocation;
            }

            $this->close($actor, $allocation, ['reason' => $data['reason'] ?? 'transfer']);

            return $this->create($actor, $targetBed, $patient, [
                'reason' => $data['reason'] ?? 'transfer',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /** @return array<string, mixed> */
    protected function summary(BedAllocation $allocation): array
    {
        return [
            'bed_id' => $allocation->bed_id,
            'patient_id' => $allocation->patient_id,
            'started_at' => $allocation->started_at->toDateTimeString(),
            'ended_at' => $allocation->ended_at?->toDateTimeString(),
            'reason' => $allocation->reason,
        ];
    }

    protected function assertBedCanBeAllocated(Bed $bed): void
    {
        if ($bed->status !== BedStatus::Available) {
            throw new BedInUse(__('This bed is not available for an allocation.'));
        }

        if (! Schema::hasTable('bed_allocations')) {
            return;
        }

        if (DB::table('bed_allocations')->where('bed_id', $bed->id)->whereNull('ended_at')->exists()) {
            throw new BedInUse(__('This bed already has an active allocation.'));
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    protected function recordEvent(User $actor, Bed $bed, string $action, array $details): void
    {
        BedEvent::query()->create([
            'bed_id' => $bed->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'details' => $details,
            'occurred_at' => now(),
        ]);
    }
}
