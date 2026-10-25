<?php

namespace Tests\Feature\Clinical;

use App\Enums\BedStatus;
use App\Enums\Sex;
use App\Models\Bed;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Team;
use App\Models\TreatmentStatus;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncounterCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function ward(): Ward
    {
        return Ward::query()->create([
            'code' => 'ED-A',
            'name' => 'Emergency observation',
            'capacity' => 4,
        ]);
    }

    protected function patient(): Patient
    {
        return Patient::query()->create([
            'patient_code' => 'P-1001',
            'fullname' => 'Ada Stone',
            'sex' => Sex::Female,
            'age' => 37,
        ]);
    }

    protected function team(): Team
    {
        return Team::query()->create([
            'name' => 'Rapid assessment',
            'status' => 'active',
        ]);
    }

    protected function treatmentStatus(): TreatmentStatus
    {
        return TreatmentStatus::query()->create([
            'slug' => 'triage',
            'label' => 'Triage',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_nurse_can_create_an_admission_and_allocate_the_selected_bed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('nurse');

        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $patient = $this->patient();
        $team = $this->team();
        $status = $this->treatmentStatus();

        $this->actingAs($user)
            ->post(route('encounters.store', absolute: false), [
                'patient_id' => $patient->id,
                'ward_id' => $ward->id,
                'bed_id' => $bed->id,
                'team_id' => $team->id,
                'treatment_status_id' => $status->id,
                'notes' => 'Admitted for observation.',
            ])
            ->assertRedirect(route('patients.index', absolute: false));

        $encounter = Encounter::query()->sole();
        $this->assertSame($patient->id, $encounter->patient_id);
        $this->assertSame($ward->id, $encounter->ward_id);
        $this->assertSame($bed->id, $encounter->bed_id);
        $this->assertSame($team->id, $encounter->team_id);
        $this->assertSame($status->id, $encounter->treatment_status_id);
        $this->assertNotNull($encounter->arrived_at);
        $this->assertNull($encounter->discharged_at);

        $bed->refresh();
        $this->assertSame(BedStatus::Occupied, $bed->status);
        $this->assertDatabaseHas('bed_allocations', [
            'bed_id' => $bed->id,
            'patient_id' => $patient->id,
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'encounter.created',
            'record_id' => $encounter->id,
        ]);
    }

    public function test_hospital_administrator_can_transfer_a_patient_to_a_new_bed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('nurse');

        $sourceWard = $this->ward();
        $targetWard = Ward::query()->create([
            'code' => 'ED-B',
            'name' => 'Emergency overflow',
            'capacity' => 4,
        ]);

        $sourceBed = Bed::query()->create([
            'ward_id' => $sourceWard->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $targetBed = Bed::query()->create([
            'ward_id' => $targetWard->id,
            'number' => 'B-01',
            'status' => 'available',
        ]);

        $patient = $this->patient();
        $team = $this->team();
        $status = $this->treatmentStatus();

        $this->actingAs($user)
            ->post(route('encounters.store', absolute: false), [
                'patient_id' => $patient->id,
                'ward_id' => $sourceWard->id,
                'bed_id' => $sourceBed->id,
                'team_id' => $team->id,
                'treatment_status_id' => $status->id,
                'notes' => 'Admitted for observation.',
            ]);

        $encounter = Encounter::query()->sole();

        $this->actingAs($user)
            ->put(route('encounters.update', ['encounter' => $encounter->id], absolute: false), [
                'patient_id' => $patient->id,
                'ward_id' => $targetWard->id,
                'bed_id' => $targetBed->id,
                'team_id' => $team->id,
                'treatment_status_id' => $status->id,
                'notes' => 'Transferred to overflow ward.',
            ])
            ->assertRedirect(route('patients.index', absolute: false));

        $encounter->refresh();
        $this->assertSame($targetWard->id, $encounter->ward_id);
        $this->assertSame($targetBed->id, $encounter->bed_id);

        $sourceBed->refresh();
        $targetBed->refresh();
        $this->assertSame(BedStatus::Cleaning, $sourceBed->status);
        $this->assertSame(BedStatus::Occupied, $targetBed->status);

        $this->assertDatabaseHas('bed_allocations', [
            'bed_id' => $targetBed->id,
            'patient_id' => $patient->id,
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'encounter.updated',
            'record_id' => $encounter->id,
        ]);
    }

    public function test_nurse_can_discharge_an_active_admission_and_close_the_allocation(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('nurse');

        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $patient = $this->patient();
        $team = $this->team();
        $status = $this->treatmentStatus();

        $this->actingAs($user)
            ->post(route('encounters.store', absolute: false), [
                'patient_id' => $patient->id,
                'ward_id' => $ward->id,
                'bed_id' => $bed->id,
                'team_id' => $team->id,
                'treatment_status_id' => $status->id,
                'notes' => 'Admitted for observation.',
            ]);

        $encounter = Encounter::query()->sole();

        $this->actingAs($user)
            ->delete(route('encounters.discharge', ['encounter' => $encounter->id], absolute: false))
            ->assertRedirect(route('patients.index', absolute: false));

        $encounter->refresh();
        $this->assertNotNull($encounter->discharged_at);

        $bed->refresh();
        $this->assertSame(BedStatus::Cleaning, $bed->status);

        $this->assertTrue(
            $encounter->patient()->exists() &&
            (bool) \Illuminate\Support\Facades\DB::table('bed_allocations')
                ->where('bed_id', $bed->id)
                ->where('patient_id', $patient->id)
                ->whereNotNull('ended_at')
                ->exists(),
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'encounter.discharged',
            'record_id' => $encounter->id,
        ]);
    }

    public function test_admissions_index_lists_recent_admissions_and_supports_filters(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('nurse');

        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $patient = $this->patient();
        $team = $this->team();
        $status = $this->treatmentStatus();

        $this->actingAs($user)
            ->post(route('encounters.store', absolute: false), [
                'patient_id' => $patient->id,
                'ward_id' => $ward->id,
                'bed_id' => $bed->id,
                'team_id' => $team->id,
                'treatment_status_id' => $status->id,
                'notes' => 'Admitted for observation.',
            ]);

        $this->actingAs($user)
            ->get(route('encounters.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('encounters/index')
                ->has('encounters', 1)
                ->where('encounters.0.patient.fullname', $patient->fullname)
                ->where('encounters.0.ward.code', $ward->code));

        $this->actingAs($user)
            ->get(route('encounters.index', ['q' => $patient->patient_code], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('encounters', 1)
                ->where('encounters.0.patient.fullname', $patient->fullname));
    }
}
