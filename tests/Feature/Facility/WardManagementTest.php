<?php

namespace Tests\Feature\Facility;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Ward;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * CIMA-044: ward CRUD (code + name + declared capacity). Covers permission
 * boundaries (ward manager + hospital administrator), validation, capacity
 * bounds and the audit trail. The unsafe-delete guard activates in CIMA-045
 * once the beds table exists; until then empty wards delete freely.
 */
class WardManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function userWithRole(string $name): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($name);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->get(route('wards.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_roles_without_wards_permissions_are_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['nurse', 'emergency department manager'] as $roleName) {
            $actor = User::factory()->create();
            $actor->assignRole($roleName);

            $this->actingAs($actor)->get(route('wards.index', absolute: false))->assertForbidden();

            $this->actingAs($actor)
                ->post(route('wards.store', absolute: false), [
                    'code' => 'ED-A',
                    'name' => 'Sneaky ward',
                    'capacity' => 10,
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('wards', 0);
    }

    public function test_ward_manager_can_create_a_ward(): void
    {
        $actor = $this->userWithRole('ward manager');

        $this->actingAs($actor)->get(route('wards.index', absolute: false))->assertOk();

        $this->actingAs($actor)
            ->post(route('wards.store', absolute: false), [
                'code' => 'ED-A',
                'name' => 'Emergency observation',
                'capacity' => 24,
            ])
            ->assertRedirect(route('wards.index'));

        $ward = Ward::query()->where('code', 'ED-A')->firstOrFail();
        $this->assertSame('Emergency observation', $ward->name);
        $this->assertSame(24, $ward->capacity);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'ward.created',
            'record_id' => $ward->id,
        ]);
    }

    public function test_store_validator_catches_bad_input(): void
    {
        $actor = $this->userWithRole('hospital administrator');

        $this->actingAs($actor)
            ->post(route('wards.store', absolute: false), [
                'code' => 'ED A?',
                'name' => '',
                'capacity' => 0,
            ])
            ->assertSessionHasErrors(['code', 'name', 'capacity']);

        $this->assertDatabaseCount('wards', 0);
    }

    public function test_capacity_is_bounded(): void
    {
        $actor = $this->userWithRole('hospital administrator');

        $this->actingAs($actor)
            ->post(route('wards.store', absolute: false), [
                'code' => 'ED-B',
                'name' => 'Emergency beds B',
                'capacity' => Ward::MAX_CAPACITY + 1,
            ])
            ->assertSessionHasErrors(['capacity']);

        $this->assertDatabaseCount('wards', 0);
    }

    public function test_duplicate_ward_code_and_name_are_rejected(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        Ward::query()->create(['code' => 'ED-A', 'name' => 'Emergency observation', 'capacity' => 10]);

        $this->actingAs($actor)
            ->post(route('wards.store', absolute: false), [
                'code' => 'ED-A',
                'name' => 'Emergency observation',
                'capacity' => 10,
            ])
            ->assertSessionHasErrors(['code', 'name']);
    }

    public function test_case_look_alike_codes_are_rejected(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        Ward::query()->create(['code' => 'ED-A', 'name' => 'Emergency observation', 'capacity' => 10]);
        $other = Ward::query()->create(['code' => 'ED-B', 'name' => 'Emergency beds B', 'capacity' => 10]);

        // Codes are normalised to upper case on validation, so re-pressed
        // look-alikes ('ed-a') collide with 'ED-A' instead of slipping
        // through as a second record.
        $this->actingAs($actor)
            ->put(route('wards.update', ['ward' => $other->id], absolute: false), [
                'code' => 'ed-a',
                'name' => 'Emergency beds B',
                'capacity' => 12,
            ])
            ->assertSessionHasErrors(['code']);
    }

    public function test_administrator_can_update_a_ward(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        $ward = Ward::query()->create(['code' => 'ed-c', 'name' => ' trauma bay ', 'capacity' => 8]);

        $this->actingAs($actor)
            ->put(route('wards.update', ['ward' => $ward->id], absolute: false), [
                'code' => 'ED-C',
                'name' => 'Trauma bay',
                'capacity' => 12,
            ])
            ->assertRedirect(route('wards.index'));

        $ward->refresh();
        $this->assertSame('ED-C', $ward->code);
        $this->assertSame('Trauma bay', $ward->name);
        $this->assertSame(12, $ward->capacity);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'ward.updated',
            'record_id' => $ward->id,
        ]);
    }

    public function test_empty_wards_can_be_deleted_and_the_trail_survives(): void
    {
        $actor = $this->userWithRole('ward manager');
        $ward = Ward::query()->create(['code' => 'ED-OLD', 'name' => 'Old ward', 'capacity' => 6]);

        $this->actingAs($actor)
            ->delete(route('wards.destroy', ['ward' => $ward->id], absolute: false))
            ->assertRedirect(route('wards.index'));

        $this->assertDatabaseMissing('wards', ['id' => $ward->id]);

        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'ward.deleted')->where('record_id', $ward->id)->count(),
        );

        $deletedState = AuditLog::query()
            ->where('action', 'ward.deleted')
            ->where('record_id', $ward->id)
            ->firstOrFail();
        $this->assertSame('ED-OLD', $deletedState->before['code']);
        $this->assertNull($deletedState->after);
    }
}
