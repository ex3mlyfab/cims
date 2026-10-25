<?php

namespace Tests\Feature\Admin;

use App\Exceptions\RoleInUse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 * CIMA-041: roles & permissions UI. Cover the permission boundary, the
 * Super Admin protection (ADR-002) and assign/unassign permission flows.
 */
class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function userWithRole(string $name): User
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $user->assignRole($name);

        return $user;
    }

    protected function admin(): User
    {
        return $this->userWithRole('hospital administrator');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('roles.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_users_without_roles_view_are_forbidden(): void
    {
        $nurse = $this->userWithRole('nurse');

        $this->actingAs($nurse)
            ->get(route('roles.index', absolute: false))
            ->assertForbidden();

        $this->actingAs($nurse)
            ->post(route('roles.store', absolute: false), [
                'name' => 'sneaky role',
                'permissions' => [],
            ])
            ->assertForbidden();

        $this->assertSame(10, Role::query()->count()); // seeded count unchanged
    }

    public function test_hospital_administrator_can_view_the_registry(): void
    {
        $user = $this->admin();

        $pageData = $this->actingAs($user)
            ->get(route('roles.index', absolute: false))
            ->assertOk()
            ->viewData('page');

        $page = is_array($pageData)
            ? $pageData
            : (array) json_decode((string) $pageData, true);

        $this->assertSame(10, count($page['props']['roles']));
        $this->assertContains(
            true,
            array_column($page['props']['roles'], 'superAdmin'),
        );
    }

    public function test_administrator_can_create_a_role(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('roles.store', absolute: false), [
                'name' => 'immunization officer',
                'permissions' => ['patients.view', 'patients.create'],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::query()->where('name', 'immunization officer')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('patients.view'));
        $this->assertTrue($role->hasPermissionTo('patients.create'));
        $this->assertFalse($role->hasPermissionTo('beds.allocate'));
    }

    public function test_create_validates_name_and_permissions(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('roles.store', absolute: false), [
                'name' => 'super admin',
                'permissions' => ['patients.view', 'made-up-permission'],
            ])
            ->assertSessionHasErrors(['name', 'permissions.1']);

        // Duplicate names are rejected too.
        $this->actingAs($user)
            ->post(route('roles.store', absolute: false), [
                'name' => 'nurse',
                'permissions' => [],
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_administrator_can_update_a_role(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put(
                route('roles.update', ['role' => Role::query()->where('name', 'nurse')->firstOrFail()->id], absolute: false),
                [
                    'name' => 'nurse',
                    'permissions' => ['patients.view', 'beds.view'],
                ],
            )
            ->assertRedirect(route('roles.index'));

        $nurse = Role::query()->where('name', 'nurse')->firstOrFail();
        $this->assertTrue($nurse->hasPermissionTo('beds.view'));
        $this->assertFalse($nurse->hasPermissionTo('patients.create'));
    }

    public function test_super_admin_role_is_protected_from_update_and_delete(): void
    {
        $user = $this->admin();
        $superAdmin = Role::query()->where('name', Role::SUPER_ADMIN)->firstOrFail();

        $this->actingAs($user)
            ->put(route('roles.update', ['role' => $superAdmin->id], absolute: false), [
                'name' => 'renamed admin',
                'permissions' => ['patients.view'],
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('roles.destroy', ['role' => $superAdmin->id], absolute: false))
            ->assertForbidden();

        $this->assertTrue(
            Role::query()->where('name', Role::SUPER_ADMIN)->exists(),
            'super admin role must remain intact',
        );
    }

    public function test_reserved_name_cannot_be_taken_by_edit(): void
    {
        $user = $this->admin();
        $nurse = Role::query()->where('name', 'nurse')->firstOrFail();

        $this->actingAs($user)
            ->put(route('roles.update', ['role' => $nurse->id], absolute: false), [
                'name' => Role::SUPER_ADMIN,
                'permissions' => ['patients.view'],
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_administrator_can_delete_an_unassigned_role_but_not_an_assigned_one(): void
    {
        $user = $this->admin();

        $unassigned = Role::create(['name' => 'disposable', 'guard_name' => 'web']);
        $assigned = Role::create(['name' => 'assigned', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($assigned);

        $this->actingAs($user)
            ->delete(route('roles.destroy', ['role' => $assigned->id], absolute: false))
            ->assertRedirect();
        $this->assertTrue(Role::query()->where('name', 'assigned')->exists());

        $this->actingAs($user)
            ->delete(route('roles.destroy', ['role' => $unassigned->id], absolute: false))
            ->assertRedirect(route('roles.index'));
        $this->assertFalse(Role::query()->where('name', 'disposable')->exists());
    }

    public function test_service_throws_role_in_use_with_the_expected_message(): void
    {
        $this->seedRoles();
        $role = Role::create(['name' => 'assigned two', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        $this->expectException(RoleInUse::class);

        app(RoleService::class)->delete($role);
    }

    public function test_super_admin_retains_all_permissions_after_registry_changes(): void
    {
        $this->seedRoles();
        $user = $this->admin();

        // Create a brand-new permission and grant it only to a custom role.
        Permission::findOrCreate('demo.thing', 'web');

        $this->actingAs($user)
            ->put(
                route('roles.update', [
                    'role' => Role::query()->where('name', 'records officer')->firstOrFail()->id,
                ], absolute: false),
                [
                    'name' => 'records officer',
                    'permissions' => ['patients.view', 'demo.thing'],
                ],
            )
            ->assertRedirect(route('roles.index'));

        $superAdmin = Role::query()->where('name', Role::SUPER_ADMIN)->firstOrFail();
        // Gate::before keeps every new registry permission available to users
        // holding the Super Admin role (ADR-002).
        $superUser = User::factory()->create()->assignRole($superAdmin);
        $this->assertTrue($superUser->can('demo.thing'));
    }

    public function test_new_role_permissions_are_seeded_idempotently(): void
    {
        $this->seedRoles();
        $rolesCount = Role::query()->count();
        $permissionsCount = Permission::query()->count();

        $this->seed(RolePermissionSeeder::class);

        $this->assertSame($rolesCount, Role::query()->count());
        $this->assertSame($permissionsCount, Permission::query()->count());

        $admin = Role::query()->where('name', 'hospital administrator')->firstOrFail();
        $this->assertTrue($admin->hasPermissionTo('roles.update'));
        $this->assertTrue($admin->hasPermissionTo('roles.delete'));
    }
}
