<?php

namespace App\Services;

use App\Enums\Sex;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CIMA-050: patient registry lifecycle. Mutations run in transactions and
 * are audited (ADR-004; the audit summary stores the registry fields, not
 * narrative clinical content). Authorization is enforced by route
 * middleware + policy; this service is the defense-in-depth layer.
 *
 * Duplicate detection (R5) is manual and search-based for now: the UI is
 * search-first, and `patient_code` is DB-unique with form-request validation.
 */
class PatientService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {fullname: string, patient_code: string, sex: string, age: int}
     */
    public function create(User $actor, array $data): Patient
    {
        return DB::transaction(function () use ($data): Patient {
            $patient = new Patient;
            $patient->patient_code = (string) $data['patient_code'];
            $patient->fullname = (string) $data['fullname'];
            $patient->sex = Sex::from((string) $data['sex']);
            $patient->age = (int) $data['age'];
            $patient->save();

            $this->auditLogger->record('patient.created', $patient, after: $this->summary($patient));

            return $patient;
        });
    }

    /**
     * Audit-safe registry summary (ADR-004: position data, not narrative PHI).
     *
     * @return array<string, mixed>
     */
    protected function summary(Patient $patient): array
    {
        return [
            'patient_code' => $patient->patient_code,
            'fullname' => $patient->fullname,
            'sex' => $patient->sex->value,
            'age' => $patient->age,
        ];
    }
}
