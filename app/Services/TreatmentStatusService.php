<?php

namespace App\Services;

use App\Exceptions\TreatmentStatusInUse;
use App\Models\TreatmentStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-052: treatment-status registry lifecycle. Mutations run in
 * transactions and are audited (ADR-004). Authorization is enforced by
 * route middleware + policy; this service is the defense-in-depth layer.
 *
 * In-use protection: once the `encounters` table exists (CIMA-060), a
 * status referenced by any admission record cannot be deleted — deactivated
 * instead. The lookup is guarded by Schema::hasTable so requests are safe
 * on unmigrated contexts (same pattern as WardService/BedService), and it
 * goes live automatically when CIMA-060 lands.
 */
class TreatmentStatusService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {slug: string, label: string, sort_order: int, is_active: bool}
     */
    public function create(User $actor, array $data): TreatmentStatus
    {
        return DB::transaction(function () use ($actor, $data): TreatmentStatus {
            $status = new TreatmentStatus;
            $status->slug = (string) $data['slug'];
            $status->label = (string) $data['label'];
            $status->sort_order = (int) $data['sort_order'];
            $status->is_active = (bool) $data['is_active'];
            $status->save();

            $this->auditLogger->record('treatment_status.created', $status, after: $this->summary($status));

            return $status;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, TreatmentStatus $status, array $data): void
    {
        DB::transaction(function () use ($actor, $status, $data): void {
            $before = $this->summary($status);

            if (isset($data['slug']) && (string) $data['slug'] !== $status->slug) {
                $status->slug = $data['slug'];
            }

            if (isset($data['label']) && (string) $data['label'] !== $status->label) {
                $status->label = $data['label'];
            }

            if (isset($data['sort_order'])) {
                $status->sort_order = (int) $data['sort_order'];
            }

            if (array_key_exists('is_active', $data)) {
                $status->is_active = (bool) $data['is_active'];
            }

            $status->save();

            $this->auditLogger->record('treatment_status.updated', $status, before: $before, after: $this->summary($status));
        });
    }

    /**
     * @throws TreatmentStatusInUse when any admission record uses the status
     */
    public function destroy(User $actor, TreatmentStatus $status): void
    {
        DB::transaction(function () use ($actor, $status): void {
            if ($this->usageCount($status) > 0) {
                throw new TreatmentStatusInUse(
                    __('This status is used by existing admission records and cannot be deleted. Deactivate it instead.')
                );
            }

            $deleted = $this->summary($status);

            $status->delete();

            // The audit row deliberately keeps record_type/record_id without a
            // FK constraint, so the trail survives the record (ADR-004).
            $this->auditLogger->record('treatment_status.deleted', $status, before: $deleted, after: null);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(TreatmentStatus $status): array
    {
        return [
            'slug' => $status->slug,
            'label' => $status->label,
            'sort_order' => $status->sort_order,
            'is_active' => $status->is_active,
        ];
    }

    /**
     * Admission records referencing the status. 0 until the encounters
     * table exists (CIMA-060), then it drives the unsafe-delete guard.
     */
    protected function usageCount(TreatmentStatus $status): int
    {
        if (! Schema::hasTable('encounters')) {
            return 0;
        }

        return (int) DB::table('encounters')
            ->where('treatment_status_id', $status->id)
            ->count();
    }
}
