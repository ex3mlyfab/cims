<?php

namespace App\Services;

use App\Exceptions\WardInUse;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CIMA-044: ward lifecycle. Mutations run in transactions and are audited
 * (ADR-004). Authorization is enforced by route middleware + policy.
 *
 * Unsafe-delete prevention: a ward that already holds beds (CIMA-045) is
 * refused instead of cascading clinical history away. The bed lookup is
 * guarded by Schema::hasTable so requests are safe on unmigrated contexts
 * (same approach as HospitalBrandingService) and becomes live as soon as
 * the beds table exists.
 */
class WardService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {code: string, name: string, capacity: int}
     */
    public function create(User $actor, array $data): Ward
    {
        return DB::transaction(function () use ($data): Ward {
            $ward = new Ward;
            $ward->code = (string) $data['code'];
            $ward->name = (string) $data['name'];
            $ward->capacity = (int) $data['capacity'];
            $ward->save();

            $this->auditLogger->record('ward.created', $ward, after: $this->summary($ward));

            return $ward;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws WardInUse when the new capacity is below the installed bed count
     */
    public function update(User $actor, Ward $ward, array $data): void
    {
        DB::transaction(function () use ($ward, $data): void {
            $before = $this->summary($ward);

            $bedCount = $this->bedCount($ward);

            if (array_key_exists('capacity', $data)
                && (int) $data['capacity'] < $bedCount) {
                throw new WardInUse(
                    __('Cannot reduce capacity below the :count bed(s) already installed in this ward.', ['count' => $bedCount])
                );
            }

            if (isset($data['code']) && (string) $data['code'] !== $ward->code) {
                $ward->code = $data['code'];
            }

            if (isset($data['name']) && (string) $data['name'] !== $ward->name) {
                $ward->name = $data['name'];
            }

            if (isset($data['capacity'])) {
                $ward->capacity = (int) $data['capacity'];
            }

            $ward->save();

            $this->auditLogger->record('ward.updated', $ward, before: $before, after: $this->summary($ward));
        });
    }

    /**
     * @throws WardInUse when the ward still holds beds
     */
    public function destroy(User $actor, Ward $ward): void
    {
        DB::transaction(function () use ($ward): void {
            $bedCount = $this->bedCount($ward);

            if ($bedCount > 0) {
                throw new WardInUse(
                    __('This ward still has :count bed(s) installed and cannot be deleted. Remove the beds first.', ['count' => $bedCount])
                );
            }

            $deleted = $this->summary($ward);

            $ward->delete();

            // The audit row deliberately keeps record_type/record_id without a
            // FK constraint, so the trail survives the record (ADR-004).
            $this->auditLogger->record('ward.deleted', $ward, before: $deleted, after: null);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(Ward $ward): array
    {
        return [
            'code' => $ward->code,
            'name' => $ward->name,
            'capacity' => $ward->capacity,
        ];
    }

    /**
     * Installed bed count for the ward. 0 until the beds table exists
     * (CIMA-045), then it drives the unsafe-delete guard.
     */
    protected function bedCount(Ward $ward): int
    {
        if (! Schema::hasTable('beds')) {
            return 0;
        }

        return (int) DB::table('beds')->where('ward_id', $ward->id)->count();
    }
}
