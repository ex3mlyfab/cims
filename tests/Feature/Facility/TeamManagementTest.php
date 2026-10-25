<?php

namespace Tests\Feature\Facility;

use App\Models\AuditLog;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * CIMA-043: team CRUD (name + lifecycle status). Covers permission
 * boundaries (team manager + hospital administrator), validation and the
 * audit trail.
 */
class TeamManagementTest extends TestCase
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

        $this->get(route('teams.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_roles_without_teams_permissions_are_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['nurse', 'emergency department manager'] as $roleName) {
            $actor = User::factory()->create();
            $actor->assignRole($roleName);

            $this->actingAs($actor)->get(route('teams.index', absolute: false))->assertForbidden();

            $this->actingAs($actor)
                ->post(route('teams.store', absolute: false), [
                    'name' => 'Sneaky Team',
                    'status' => 'active',
                ])
                ->assertForbidden();
        }

        $this->assertDatabaseCount('teams', 0);
    }

    public function test_team_manager_can_create_a_team(): void
    {
        $actor = $this->userWithRole('team manager');

        $this->actingAs($actor)->get(route('teams.index', absolute: false))->assertOk();

        $this->actingAs($actor)
            ->post(route('teams.store', absolute: false), [
                'name' => 'Stroke response team',
                'status' => 'active',
            ])
            ->assertRedirect(route('teams.index'));

        $team = Team::query()->where('name', 'Stroke response team')->firstOrFail();
        $this->assertSame('active', $team->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'team.created',
            'record_id' => $team->id,
        ]);
    }

    public function test_store_validator_catches_bad_input(): void
    {
        $actor = $this->userWithRole('hospital administrator');

        $this->actingAs($actor)
            ->post(route('teams.store', absolute: false), [
                'name' => '',
                'status' => 'pending',
            ])
            ->assertSessionHasErrors(['name', 'status']);

        $this->assertDatabaseCount('teams', 0);
    }

    public function test_duplicate_team_name_is_rejected(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        Team::query()->create(['name' => 'Rapid response', 'status' => 'active']);

        $this->actingAs($actor)
            ->post(route('teams.store', absolute: false), [
                'name' => 'Rapid response',
                'status' => 'inactive',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_administrator_can_update_a_team(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        $team = Team::query()->create(['name' => ' trauma team ', 'status' => 'active']);

        $this->actingAs($actor)
            ->put(route('teams.update', ['team' => $team->id], absolute: false), [
                'name' => 'Trauma team',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('teams.index'));

        $team->refresh();
        $this->assertSame('Trauma team', $team->name);
        $this->assertSame('inactive', $team->status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'team.updated',
            'record_id' => $team->id,
        ]);
    }

    public function test_teams_can_be_deleted_and_the_trail_survives(): void
    {
        $actor = $this->userWithRole('team manager');
        $team = Team::query()->create(['name' => 'Old team', 'status' => 'inactive']);

        $this->actingAs($actor)
            ->delete(route('teams.destroy', ['team' => $team->id], absolute: false))
            ->assertRedirect(route('teams.index'));

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);

        $this->assertSame(
            1,
            AuditLog::query()->where('action', 'team.deleted')->where('record_id', $team->id)->count(),
        );

        $deletedState = AuditLog::query()
            ->where('action', 'team.deleted')
            ->where('record_id', $team->id)
            ->firstOrFail();
        $this->assertSame('Old team', $deletedState->before['name']);
        $this->assertNull($deletedState->after);
    }
}
