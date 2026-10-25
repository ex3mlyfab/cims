<?php

namespace App\Services;

use App\Enums\BedStatus;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Team;
use App\Models\TreatmentStatus;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CIMA-051: create the admission record and the matching bed allocation in one
 * transaction. The admission is the patient-level record; the allocation is the
 * bed-level occupancy record used by CIMA-060.
 */
class EncounterService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Encounter
    {
        return DB::transaction(function () use ($actor, $data): Encounter {
            $patient = Patient::query()->whereKey($data['patient_id'])->firstOrFail();
            $ward = Ward::query()->whereKey($data['ward_id'])->firstOrFail();
            $bed = Bed::query()
                ->where('ward_id', $ward->id)
                ->whereKey($data['bed_id'])
                ->firstOrFail();

            $team = Team::query()->whereKey($data['team_id'])->firstOrFail();
            $treatmentStatus = TreatmentStatus::query()->whereKey($data['treatment_status_id'])->firstOrFail();

            if (! $treatmentStatus->is_active) {
                throw new BedInUse(__('The selected treatment status is inactive and cannot be used for a new admission.'));
            }

            if ($bed->status !== BedStatus::Available) {
                throw new BedInUse(__('The selected bed is not available for a new admission.'));
            }

            app(BedAllocationService::class)->create($actor, $bed, $patient, [
                'reason' => 'admission',
                'notes' => $data['notes'] ?? null,
            ]);

            $encounter = new Encounter;
            $encounter->patient_id = $patient->id;
            $encounter->ward_id = $ward->id;
            $encounter->bed_id = $bed->id;
            $encounter->team_id = $team->id;
            $encounter->treatment_status_id = $treatmentStatus->id;
            $encounter->arrived_at = $data['arrived_at'] ?? now();
            $encounter->notes = $data['notes'] ?? null;
            $encounter->save();

            $this->auditLogger->record('encounter.created', $encounter, after: $this->summary($encounter));

            return $encounter;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Encounter $encounter, array $data): Encounter
    {
        return DB::transaction(function () use ($actor, $encounter, $data): Encounter {
            $before = $this->summary($encounter);

            $patient = $encounter->patient()->firstOrFail();
            $ward = Ward::query()->whereKey($data['ward_id'] ?? $encounter->ward_id)->firstOrFail();
            $bed = Bed::query()
                ->where('ward_id', $ward->id)
                ->whereKey($data['bed_id'] ?? $encounter->bed_id)
                ->firstOrFail();
            $team = Team::query()->whereKey($data['team_id'] ?? $encounter->team_id)->firstOrFail();
            $treatmentStatus = TreatmentStatus::query()->whereKey($data['treatment_status_id'] ?? $encounter->treatment_status_id)->firstOrFail();

            if (! $treatmentStatus->is_active) {
                throw new BedInUse(__('The selected treatment status is inactive and cannot be used for an admission update.'));
            }

            $targetBedChanged = ($data['bed_id'] ?? $encounter->bed_id) !== $encounter->bed_id;
            $targetWardChanged = ($data['ward_id'] ?? $encounter->ward_id) !== $encounter->ward_id;

            if ($targetBedChanged || $targetWardChanged) {
                $activeAllocation = BedAllocation::query()
                    ->where('bed_id', $encounter->bed_id)
                    ->whereNull('ended_at')
                    ->first();

                if ($activeAllocation !== null) {
                    app(BedAllocationService::class)->transfer($actor, $activeAllocation, $bed, $patient, [
                        'reason' => 'transfer',
                        'notes' => $data['notes'] ?? null,
                    ]);
                } elseif ($bed->status === BedStatus::Available) {
                    app(BedAllocationService::class)->create($actor, $bed, $patient, [
                        'reason' => 'transfer',
                        'notes' => $data['notes'] ?? null,
                    ]);
                } elseif ($bed->status !== BedStatus::Occupied || $bed->id !== $encounter->bed_id) {
                    throw new BedInUse(__('The selected bed is not available for a transfer.'));
                }
            }

            $encounter->ward_id = $ward->id;
            $encounter->bed_id = $bed->id;
            $encounter->team_id = $team->id;
            $encounter->treatment_status_id = $treatmentStatus->id;
            $encounter->arrived_at = $data['arrived_at'] ?? $encounter->arrived_at;
            $encounter->notes = array_key_exists('notes', $data) ? $data['notes'] : $encounter->notes;
            $encounter->save();

            $this->auditLogger->record('encounter.updated', $encounter, before: $before, after: $this->summary($encounter));

            return $encounter->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function discharge(User $actor, Encounter $encounter, array $data = []): Encounter
    {
        return DB::transaction(function () use ($actor, $encounter, $data): Encounter {
            if ($encounter->discharged_at !== null) {
                throw new BedInUse(__('This admission has already been discharged.'));
            }

            $before = $this->summary($encounter);
            $activeAllocation = BedAllocation::query()
                ->where('patient_id', $encounter->patient_id)
                ->whereNull('ended_at')
                ->first();

            if ($activeAllocation !== null) {
                app(BedAllocationService::class)->close($actor, $activeAllocation, [
                    'reason' => 'discharge',
                    'notes' => $data['notes'] ?? $encounter->notes,
                    'ended_at' => $data['discharged_at'] ?? now(),
                ]);
            }

            $encounter->discharged_at = $data['discharged_at'] ?? now();
            $encounter->notes = array_key_exists('notes', $data) ? $data['notes'] : $encounter->notes;
            $encounter->save();

            $this->auditLogger->record('encounter.discharged', $encounter, before: $before, after: $this->summary($encounter));

            return $encounter->refresh();
        });
    }

    /** @return array<string, mixed> */
    protected function summary(Encounter $encounter): array
    {
        return [
            'patient_id' => $encounter->patient_id,
            'ward_id' => $encounter->ward_id,
            'bed_id' => $encounter->bed_id,
            'team_id' => $encounter->team_id,
            'treatment_status_id' => $encounter->treatment_status_id,
            'arrived_at' => $encounter->arrived_at ? Carbon::parse((string) $encounter->arrived_at)->toDateTimeString() : null,
            'discharged_at' => $encounter->discharged_at ? Carbon::parse((string) $encounter->discharged_at)->toDateTimeString() : null,
        ];
    }
}
