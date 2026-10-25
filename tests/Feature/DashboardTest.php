<?php

namespace Tests\Feature;

use App\Enums\BedStatus;
use App\Enums\Sex;
use App\Models\Bed;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_shows_live_admission_and_bed_metrics_to_clinical_staff(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $ward = Ward::query()->create([
            'code' => 'ED-A',
            'name' => 'Emergency observation',
            'capacity' => 6,
        ]);
        $beds = collect([
            BedStatus::Occupied,
            BedStatus::Available,
            BedStatus::Cleaning,
            BedStatus::OutOfService,
        ])->map(fn (BedStatus $status, int $index) => Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-0'.($index + 1),
            'status' => $status,
        ]));
        $patient = Patient::query()->create([
            'patient_code' => 'P-1001',
            'fullname' => 'Ada Stone',
            'sex' => Sex::Female,
            'age' => 37,
        ]);
        Encounter::query()->create([
            'patient_id' => $patient->id,
            'ward_id' => $ward->id,
            'bed_id' => $beds->first()->id,
            'arrived_at' => now(),
        ]);

        $this->actingAs($nurse)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.activeInpatients', 1)
                ->where('stats.admissionsToday', 1)
                ->where('stats.patientRegistryCount', 1)
                ->where('stats.installedBeds', 4)
                ->where('stats.occupiedBeds', 1)
                ->where('stats.availableBeds', 1)
                ->where('stats.bedsBeingServiced', 2)
                ->where('stats.bedOccupancyRate', 25)
                ->where('wards.0.code', 'ED-A')
                ->where('wards.0.installedBeds', 4)
                ->where('wards.0.occupiedBeds', 1)
                ->has('recentAdmissions', 1)
                ->where('recentAdmissions.0.patient.fullname', 'Ada Stone'));
    }

    public function test_dashboard_does_not_expose_patient_details_without_clinical_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $administrator = User::factory()->create();
        $administrator->assignRole('hospital administrator');

        $ward = Ward::query()->create([
            'code' => 'ED-A',
            'name' => 'Emergency observation',
            'capacity' => 2,
        ]);
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => BedStatus::Occupied,
        ]);
        $patient = Patient::query()->create([
            'patient_code' => 'P-1001',
            'fullname' => 'Ada Stone',
            'sex' => Sex::Female,
            'age' => 37,
        ]);
        Encounter::query()->create([
            'patient_id' => $patient->id,
            'ward_id' => $ward->id,
            'bed_id' => $bed->id,
            'arrived_at' => now(),
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.activeInpatients', null)
                ->where('stats.admissionsToday', null)
                ->where('stats.patientRegistryCount', null)
                ->has('recentAdmissions', 0)
                ->where('wards.0.occupiedBeds', 1));
    }

    public function test_patient_registry_metrics_are_available_to_records_officers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $recordsOfficer = User::factory()->create();
        $recordsOfficer->assignRole('records officer');

        Patient::query()->create([
            'patient_code' => 'P-1001',
            'fullname' => 'Ada Stone',
            'sex' => Sex::Female,
            'age' => 37,
        ]);

        $this->actingAs($recordsOfficer)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->where('stats.patientRegistryCount', 1)
                ->where('stats.activeInpatients', null)
                ->where('stats.installedBeds', null)
                ->has('recentAdmissions', 0));
    }
}
