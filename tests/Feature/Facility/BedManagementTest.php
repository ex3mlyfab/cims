<?php

namespace Tests\Feature\Facility;

use App\Enums\BedStatus;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\User;
use App\Models\Ward;
use App\Services\BedService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * CIMA-045: bed CRUD + manual bed states. Covers permission boundaries
 * (ward manager / bed manager manage; ED staff + nurses only view),
 * per-ward number uniqueness, declared-ward-capacity enforcement, the
 * state-machine guard around `occupied` (CIMA-060's allocation workflow)
 * and the audit trail.
 */
class BedManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function userWithRole(string $name): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($name);

        return $user;
    }

    protected function ward(array $attributes = []): Ward
    {
        return Ward::query()->create(array_merge([
            'code' => 'ED-A',
            'name' => 'Emergency observation',
            'capacity' => 4,
        ], $attributes));
    }

    /**
     * assertSessionHas callback: the Inertia flash bag carries an error toast.
     */
    protected function errorToast(): callable
    {
        return fn ($flash): bool => ($flash['toast']['type'] ?? '') === 'error';
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->get(route('beds.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_roles_without_beds_permissions_are_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $ward = $this->ward();

        foreach (['nurse', 'emergency department manager'] as $roleName) {
            $actor = User::factory()->create();
            $actor->assignRole($roleName);

            // They hold beds.view (allocation preview later in CIMA-060)...
            $this->actingAs($actor)->get(route('beds.index', absolute: false))->assertOk();

            // ...but never beds.manage.
            $this->actingAs($actor)
                ->get(route('beds.create', absolute: false))
                ->assertForbidden();

            $this->actingAs($actor)
                ->post(route('beds.store', absolute: false), [
                    'ward_id' => $ward->id,
                    'number' => 'A-01',
                    'status' => 'available',
                ])
                ->assertForbidden();
        }

        // No beds permission at all.
        $outsider = User::factory()->create();
        $outsider->assignRole('team manager');
        $this->actingAs($outsider)->get(route('beds.index', absolute: false))->assertForbidden();

        $this->assertDatabaseCount('beds', 0);
    }

    public function test_ward_manager_can_create_a_bed_and_it_is_audited(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = $this->ward();

        $this->actingAs($actor)->get(route('beds.index', absolute: false))->assertOk();
        $this->actingAs($actor)->get(route('beds.create', absolute: false))->assertOk();

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => $ward->id,
                'number' => 'A-01',
                'status' => 'available',
            ])
            ->assertRedirect(route('beds.index'));

        $bed = Bed::query()->where('number', 'A-01')->firstOrFail();
        $this->assertSame($ward->id, $bed->ward_id);
        $this->assertSame(BedStatus::Available, $bed->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'bed.created',
            'record_id' => $bed->id,
        ]);
    }

    public function test_store_validator_catches_bad_input(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = $this->ward();

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => $ward->id,
                'number' => 'Not a number!',
                'status' => 'occupied',
            ])
            ->assertSessionHasErrors(['number', 'status']);

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => 'missing',
                'number' => '',
                'status' => 'available',
            ])
            ->assertSessionHasErrors(['ward_id', 'number']);

        $this->assertDatabaseCount('beds', 0);
    }

    public function test_bed_numbers_are_unique_per_ward_but_sharable_across_wards(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        $wardA = $this->ward(['code' => 'ED-A', 'name' => 'Emergency observation', 'capacity' => 9]);
        $wardB = $this->ward(['code' => 'ED-B', 'name' => 'Emergency beds B', 'capacity' => 9]);

        $payload = fn (string $wardId): array => [
            'ward_id' => $wardId,
            'number' => 'A-01',
            'status' => 'available',
        ];

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), $payload($wardA->id))
            ->assertRedirect(route('beds.index'));

        // Same number inside the same ward is refused...
        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), $payload($wardA->id))
            ->assertSessionHasErrors(['number']);

        // ...but fine in another ward.
        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), $payload($wardB->id))
            ->assertRedirect(route('beds.index'));

        $this->assertSame(2, Bed::query()->count());
    }

    public function test_bed_numbers_are_normalised_to_upper_case(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = $this->ward();

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => $ward->id,
                'number' => 'b-2',
                'status' => 'available',
            ])
            ->assertRedirect(route('beds.index'));

        $this->assertSame(1, Bed::query()->where('number', 'B-2')->count());
    }

    public function test_creating_a_bed_past_the_declared_ward_capacity_is_refused(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = $this->ward(['capacity' => 1]);

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => $ward->id,
                'number' => 'A-01',
                'status' => 'available',
            ])
            ->assertRedirect(route('beds.index'));

        $this->actingAs($actor)
            ->post(route('beds.store', absolute: false), [
                'ward_id' => $ward->id,
                'number' => 'A-02',
                'status' => 'available',
            ])
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', $this->errorToast());

        $this->assertDatabaseCount('beds', 1);
    }

    public function test_beds_can_move_through_their_manual_states_and_are_audited(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        foreach (['cleaning', 'out_of_service', 'available'] as $status) {
            $this->actingAs($actor)
                ->put(route('beds.update', ['bed' => $bed->id], absolute: false), [
                    'number' => $bed->number,
                    'status' => $status,
                ])
                ->assertRedirect(route('beds.index'));

            $bed->refresh();
            $this->assertSame($status, $bed->status->value);
        }

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'bed.updated',
            'record_id' => $bed->id,
        ]);
    }

    public function test_service_refuses_to_enter_occupied_directly(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $this->expectException(BedInUse::class);

        app(BedService::class)->update(User::factory()->create(), $bed, ['status' => 'occupied']);
    }

    public function test_service_refuses_to_delete_an_occupied_bed(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => BedStatus::Occupied,
        ]);

        $this->expectException(BedInUse::class);

        app(BedService::class)->destroy(User::factory()->create(), $bed);
    }

    public function test_delete_of_a_free_bed_keeps_its_audit_trail(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        $ward = $this->ward();
        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $this->actingAs($actor)
            ->delete(route('beds.destroy', ['bed' => $bed->id], absolute: false))
            ->assertRedirect(route('beds.index'));

        $this->assertDatabaseMissing('beds', ['id' => $bed->id]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'bed.deleted',
            'record_id' => $bed->id,
        ]);
    }
}
