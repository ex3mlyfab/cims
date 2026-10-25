<?php

namespace Tests\Feature\Clinical;

use App\Models\Patient;
use App\Models\User;
use App\Services\PatientService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * CIMA-050: patient search & registration — the search-first front door of
 * the admission-record flow. Covers permission boundaries (view vs create),
 * the create modal flow (fullname, patient id, sex, age), duplicate
 * detection (DB-unique patient_code), search semantics, normalisation and
 * the audit trail.
 */
class PatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('patients.index', absolute: false))->assertRedirect(route('login', absolute: false));
    }

    public function test_roles_without_patients_view_cannot_search(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $teamManager = User::factory()->create();
        $teamManager->assignRole('team manager');

        $this->actingAs($teamManager)
            ->get(route('patients.index', absolute: false))
            ->assertForbidden();
    }

    public function test_view_only_roles_can_search_but_not_register(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // The doctor role carries patients.view but not patients.create.
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $this->actingAs($doctor)
            ->get(route('patients.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('patients/index')
                ->has('patients', 0));

        $this->actingAs($doctor)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Ada Obi',
                'patient_code' => 'P-000001',
                'sex' => 'female',
                'age' => 34,
            ])
            ->assertForbidden();

        $this->assertSame(0, Patient::query()->count());
    }

    public function test_records_officer_registers_a_patient_via_the_modal_payload(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $officer = User::factory()->create();
        $officer->assignRole('records officer');

        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Ada Obi',
                'patient_code' => 'p-000001',
                'sex' => 'female',
                'age' => 34,
            ])
            ->assertRedirect(route('patients.index', absolute: false))
            ->assertSessionHas('inertia.flash_data', fn ($flash): bool => ($flash['toast']['type'] ?? '') === 'success');

        $patient = Patient::query()->sole();

        $this->assertSame('P-000001', $patient->patient_code);
        $this->assertSame('Ada Obi', $patient->fullname);
        $this->assertSame('female', $patient->sex->value);
        $this->assertSame(34, $patient->age);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'patient.created',
            'record_type' => Patient::class,
            'record_id' => $patient->id,
        ]);
    }

    public function test_registration_validates_the_registry_fields(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $officer = User::factory()->create();
        $officer->assignRole('records officer');

        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'A',
                'patient_code' => 'P-1',
                'sex' => 'alien',
                'age' => 200,
            ])
            ->assertSessionHasErrors(['fullname', 'sex', 'age']);

        // A missing field fails too.
        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Ada Obi',
                'patient_code' => 'P-000002',
                'sex' => 'female',
            ])
            ->assertSessionHasErrors(['age']);

        $this->assertSame(0, Patient::query()->count());
    }

    public function test_duplicate_patient_code_is_rejected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $officer = User::factory()->create();
        $officer->assignRole('records officer');

        app(PatientService::class)->create($officer, [
            'fullname' => 'Ada Obi',
            'patient_code' => 'P-000003',
            'sex' => 'female',
            'age' => 30,
        ]);

        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Different Name',
                'patient_code' => 'P-000003',
                'sex' => 'male',
                'age' => 44,
            ])
            ->assertSessionHasErrors(['patient_code']);

        $this->assertSame(1, Patient::query()->count());
    }

    public function test_quick_registration_returns_the_created_patient_and_rejects_duplicate_codes(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $officer = User::factory()->create();
        $officer->assignRole('nurse');

        $this->actingAs($officer)
            ->postJson(route('patients.store', absolute: false), [
                'fullname' => 'Ada Obi',
                'patient_code' => 'p-quick-01',
                'sex' => 'female',
                'age' => 34,
            ])
            ->assertCreated()
            ->assertJsonPath('patient.patient_code', 'P-QUICK-01')
            ->assertJsonPath('patient.fullname', 'Ada Obi');

        $this->actingAs($officer)
            ->postJson(route('patients.store', absolute: false), [
                'fullname' => 'Different Person',
                'patient_code' => 'p-quick-01',
                'sex' => 'male',
                'age' => 44,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['patient_code']);

        $this->assertSame(1, Patient::query()->count());
    }

    public function test_patient_code_is_normalized_for_model_creations_too(): void
    {
        $patient = Patient::query()->create([
            'patient_code' => 'p-direct-01',
            'fullname' => 'Ada Obi',
            'sex' => 'female',
            'age' => 34,
        ]);

        $this->assertSame('P-DIRECT-01', $patient->patient_code);
    }

    public function test_model_creations_cannot_duplicate_a_patient_code_with_different_case(): void
    {
        Patient::query()->create([
            'patient_code' => 'p-case-01',
            'fullname' => 'Ada Obi',
            'sex' => 'female',
            'age' => 34,
        ]);

        $this->expectException(QueryException::class);

        Patient::query()->create([
            'patient_code' => 'P-CASE-01',
            'fullname' => 'Different Person',
            'sex' => 'male',
            'age' => 44,
        ]);
    }

    public function test_search_matches_fullname_and_patient_code(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $actor = User::factory()->create();
        $viewer = User::factory()->create();
        $viewer->assignRole('doctor'); // patients.view
        $service = app(PatientService::class);
        $service->create($actor, ['fullname' => 'Ada Obi', 'patient_code' => 'P-000010', 'sex' => 'female', 'age' => 30]);
        $service->create($actor, ['fullname' => 'John Ade', 'patient_code' => 'P-000011', 'sex' => 'male', 'age' => 51]);
        $service->create($actor, ['fullname' => 'Ada Okafor', 'patient_code' => 'ED-777', 'sex' => 'female', 'age' => 72]);

        $this->actingAs($viewer)
            ->get(route('patients.index', absolute: false)) // no query: everyone listed
            ->assertInertia(fn ($page) => $page->has('patients', 3));

        // Fullname substring, case-insensitive.
        $this->actingAs($viewer)
            ->get(route('patients.index', ['q' => 'oka'], absolute: false))
            ->assertInertia(fn ($page) => $page
                ->has('patients', 1)
                ->where('search_query', 'oka')
                ->where('patients.0.fullname', 'Ada Okafor'));

        // Patient code.
        $this->actingAs($viewer)
            ->get(route('patients.index', ['q' => 'p-000011'], absolute: false))
            ->assertInertia(fn ($page) => $page
                ->has('patients', 1)
                ->where('patients.0.patient_code', 'P-000011'));

        // No match: empty results, the "no match" state flag.
        $this->actingAs($viewer)
            ->get(route('patients.index', ['q' => 'zzz nobody'], absolute: false))
            ->assertInertia(fn ($page) => $page
                ->has('patients', 0)
                ->where('search_query', 'zzz nobody'));
    }

    public function test_registering_an_existing_patient_code_normalises_upper_case(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $officer = User::factory()->create();
        $officer->assignRole('records officer');

        // An existing code (mixed case in the DB fixture) whose normalised
        // spelling collides with a mixed-case submission.
        Patient::query()->create([
            'patient_code' => 'ED-777',
            'fullname' => 'Sixth Patient',
            'sex' => 'male',
            'age' => 22,
        ]);

        // Mixed case input produces the same normalised code as before.
        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Third Patient',
                'patient_code' => 'p- 000020 ',
                'sex' => 'other',
                'age' => 0,
            ]);

        $this->assertSame(1, Patient::query()->count()); // interior space fails alpha_dash → still only ED-777

        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Third Patient',
                'patient_code' => ' ed-777 ',
                'sex' => 'other',
                'age' => 19,
            ])
            ->assertSessionHasErrors(['patient_code']); // normalises to ED-777 → duplicate

        $this->actingAs($officer)
            ->post(route('patients.store', absolute: false), [
                'fullname' => 'Third Patient',
                'patient_code' => 'p-000020',
                'sex' => 'other',
                'age' => 19,
            ]);

        $this->assertSame('P-000020', Patient::query()->where('fullname', 'Third Patient')->value('patient_code'));
        $this->assertSame(19, (int) Patient::query()->where('age', 19)->value('age'));
        $this->assertSame(2, Patient::query()->count());
    }
}
