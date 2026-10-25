<?php

namespace App\Services;

use App\Enums\BedStatus;
use App\Exceptions\BedCapacityExceeded;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\BedEvent;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-045: bed lifecycle inside a ward (CRUD + state machine). Mutations run
 * in transactions and are audited (ADR-004). CIMA-046 additionally writes the
 * append-only bed_events occupation history in the same transaction.
 * Authorization is enforced by route middleware + policy; this service is the
 * defense-in-depth layer.
 *
 * Occupancy consistency (cima-clinical-safety #4): `occupied` is reserved
 * for the allocation workflow (CIMA-060) — bed management never enters or
 * leaves it directly, and once allocations exist a bed with an active
 * allocation row cannot be deleted (Schema-guarded so requests are safe on
 * unmigrated contexts, same pattern as WardService). A bed that ever wrote
 * a bed_event can no longer be hard-deleted either history must survive.
 */
class BedService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {number: string, status: string}
     *
     * @throws BedCapacityExceeded when the ward is at its declared capacity
     * @throws BedInUse when an unsupported initial status is given
     */
    public function create(User $actor, Ward $ward, array $data): Bed
    {
        return DB::transaction(function () use ($actor, $ward, $data): Bed {
            $installed = (int) $ward->beds()->count();

            if ($installed >= $ward->capacity) {
                throw new BedCapacityExceeded(
                    __('Ward :code is at its declared capacity of :capacity. Increase the ward capacity first.', [
                        'code' => $ward->code,
                        'capacity' => $ward->capacity,
                    ])
                );
            }

            $status = BedStatus::from((string) $data['status']);

            if (! in_array($status, BedStatus::manualStates(), true)) {
                throw new BedInUse(
                    __('Bed occupancy is managed through the allocation workflow and cannot be set here.')
                );
            }

            $bed = new Bed;
            $bed->ward_id = $ward->id;
            $bed->number = (string) $data['number'];
            $bed->status = $status;
            $bed->save();

            $this->auditLogger->record('bed.created', $bed, after: $this->summary($bed));

            $this->recordEvent($actor, $bed, 'bed.created', [
                'number' => $bed->number,
                'status' => $bed->status->value,
            ]);

            return $bed;
        });
    }

    /**
     * @param  array<string, mixed>  $data  shape: {number?: string, status?: string}
     *
     * @throws BedInUse on an illegal state-machine move or a manual `occupied`
     */
    public function update(User $actor, Bed $bed, array $data): void
    {
        DB::transaction(function () use ($actor, $bed, $data): void {
            $before = $this->summary($bed);
            $oldNumber = $bed->number;
            $oldStatus = $bed->status;

            $newStatus = isset($data['status'])
                ? BedStatus::from((string) $data['status'])
                : $bed->status;

            if (! in_array($newStatus, BedStatus::manualStates(), true)) {
                throw new BedInUse(
                    __('Bed occupancy is managed through the allocation workflow and cannot be set here.')
                );
            }

            if ($newStatus !== $bed->status
                && ! in_array($newStatus, $bed->status->allowedTargets(), true)) {
                throw new BedInUse(
                    __('A :from bed cannot move directly to :to.', [
                        'from' => $bed->status->label(),
                        'to' => $newStatus->label(),
                    ])
                );
            }

            if (isset($data['number']) && (string) $data['number'] !== $bed->number) {
                $bed->number = $data['number'];
            }

            $bed->status = $newStatus;
            $bed->save();

            $this->auditLogger->record('bed.updated', $bed, before: $before, after: $this->summary($bed));

            if ($bed->status !== $oldStatus) {
                $this->recordEvent($actor, $bed, 'bed.status_changed', [
                    'from' => $oldStatus->value,
                    'to' => $bed->status->value,
                ]);
            }

            if ($bed->number !== $oldNumber) {
                $this->recordEvent($actor, $bed, 'bed.number_changed', [
                    'from' => $oldNumber,
                    'to' => $bed->number,
                ]);
            }
        });
    }

    /**
     * @throws BedInUse when the bed is occupied (or actively allocated)
     */
    public function destroy(User $actor, Bed $bed): void
    {
        DB::transaction(function () use ($bed): void {
            if ($bed->status === BedStatus::Occupied) {
                throw new BedInUse(
                    __('Occupied beds cannot be deleted. End the allocation first.')
                );
            }

            if ($this->activeAllocationCount($bed) > 0) {
                throw new BedInUse(
                    __('This bed still has an active allocation and cannot be deleted.')
                );
            }

            if ($bed->events()->exists()) {
                throw new BedInUse(
                    __('This bed has recorded history (bed_events) and cannot be deleted. Move it out of service instead.')
                );
            }

            $deleted = $this->summary($bed);

            $bed->delete();

            // The audit row deliberately keeps record_type/record_id without a
            // FK constraint, so the trail survives the record (ADR-004).
            $this->auditLogger->record('bed.deleted', $bed, before: $deleted, after: null);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(Bed $bed): array
    {
        return [
            'ward' => $bed->ward->code,
            'number' => $bed->number,
            'status' => $bed->status->value,
        ];
    }

    /**
     * Active allocation count for the bed. 0 until the bed_allocations table
     * exists (CIMA-060), then it drives the unsafe-delete guard.
     */
    protected function activeAllocationCount(Bed $bed): int
    {
        if (! Schema::hasTable('bed_allocations')) {
            return 0;
        }

        return (int) DB::table('bed_allocations')
            ->where('bed_id', $bed->id)
            ->whereNull('ended_at')
            ->count();
    }

    /**
     * Append the occupation/history event (CIMA-046) inside the surrounding
     * transaction. Append-only: only inserts are ever issued.
     *
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
