<?php

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/*
 * CIMA-022/023/024: RBAC + Super Admin authorization boundary tests.
 * Every protected operation must be denied server-side without permission,
 * and the Super Admin role must hold comprehensive access (ADR-002).
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function relevantPermissions(): array
    {
        return [
            'users.create',
            'patients.view',
            'wards.manage',
            'beds.allocate',
            'settings.branding',
            'audit.view',
        ];
    }

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function userWithRole(string $name): User
    {
        $role = match ($name) {
            'super admin' => Role::findOrCreate('super admin'),
            'nurse' => Role::findOrCreate('nurse'),
            default => throw new InvalidArgumentException("Test only knows roles: super admin, nurse (got [{$name}])."),
        };

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_is_granted_every_ability(): void
    {
        $admin = $this->userWithRole('super admin');

        foreach ($this->relevantPermissions() as $name) {
            Permission::findOrCreate($name);
            $this->assertTrue($admin->can($name), "super admin should be able to [{$name}]");
        }
    }

    public function test_gate_before_returns_null_for_everyone_else(): void
    {
        $nurse = $this->userWithRole('nurse');

        foreach ($this->relevantPermissions() as $name) {
            Permission::findOrCreate($name);

            if ($nurse->hasPermissionTo($name)) {
                // Sanity check in case the seeder grants this role the permission.
                $this->assertTrue($nurse->can($name));

                continue;
            }

            $this->assertFalse($nurse->can($name), "nurse must NOT be able to [{$name}]");
        }
    }

    public function test_other_users_will_properly_deny_even_without_gate_permission(): void
    {
        $user = User::factory()->create([/* no roles */]);

        foreach ($this->relevantPermissions() as $name) {
            Permission::findOrCreate($name);
            $this->assertFalse($user->can($name));
        }
    }

    public function test_role_permission_matrix_is_seeded(): void
    {
        $this->seedRoles();

        $nurse = app(Role::class)->where('name', 'nurse')->first();
        $this->assertTrue($nurse->hasPermissionTo('patients.view'));
        $this->assertFalse($nurse->hasPermissionTo('users.create'));

        $admin = app(Role::class)->where('name', 'hospital administrator')->first();
        $this->assertTrue($admin->hasPermissionTo('settings.branding'));
        $this->assertTrue($admin->hasPermissionTo('users.reset-password'));

        $super = app(Role::class)->where('name', 'super admin')->first();
        $this->assertInstanceOf(Role::class, $super);
        // The seeder grants the Super Admin every permission explicitly so
        // permission-based UI checks resolve without the Gate::before bypass.
        $this->assertTrue($super->hasPermissionTo('beds.allocate'));
    }

    public function test_role_permission_seeder_is_idempotent(): void
    {
        $this->seedRoles();
        $rolesCount = app(Role::class)->count();
        $permissionsCount = app(Permission::class)->count();

        $this->seedRoles();

        $this->assertSame($rolesCount, app(Role::class)->count());
        $this->assertSame($permissionsCount, app(Permission::class)->count());
    }
}
