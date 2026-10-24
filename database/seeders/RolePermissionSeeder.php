<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * CIMA-022 / CIMA-023: idempotent roles & permissions seed.
 *
 * Re-running is safe: permissions and roles are created with firstOrCreate,
 * and role assignments are re-synced rather than duplicated.
 * Initial matrix = ADR-002 + docs/IMPLEMENTATION_PLAN.md §4; refine with stakeholders.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var array<string, list<string>> role => permissions (empty list = dashboard only for now) */
    public const ROLE_PERMISSIONS = [
        'super admin' => [], // granted everything via Gate::before (ADR-002)
        'hospital administrator' => [
            'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.reset-password',
            'roles.view', 'permissions.view',
            'wards.view', 'wards.manage',
            'beds.view', 'beds.manage',
            'management-team.view', 'management-team.manage',
            'settings.branding',
            'audit.view',
        ],
        'emergency department manager' => [
            'patients.view', 'patients.create', 'encounters.manage',
            'beds.view', 'beds.allocate', 'beds.transfer',
            'audit.view',
        ],
        'doctor' => [
            'patients.view', 'encounters.manage',
            'beds.view', 'beds.transfer',
        ],
        'nurse' => [
            'patients.view', 'patients.create', 'encounters.manage',
            'beds.view', 'beds.allocate', 'beds.transfer',
        ],
        'ward manager' => [
            'wards.view', 'beds.view', 'beds.manage',
        ],
        'bed manager' => [
            'beds.view', 'beds.manage', 'beds.allocate', 'beds.transfer',
        ],
        'records officer' => [
            'patients.view', 'patients.create',
        ],
        'public display operator' => [
            'display.manage', 'patients.view',
        ],
    ];

    public function run(): void
    {
        foreach (self::ROLE_PERMISSIONS as $role => $rolePermissions) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Permission::query()->upsert(
            $this->permissionRecords(),
            uniqueBy: ['name', 'guard_name'],
            update: ['name', 'guard_name'],
        );

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            if ($role === 'super admin') {
                continue;
            }

            app(Role::class)->where('name', $role)->first()->syncPermissions($permissions);
        }

        // Ensure the Super Admin explicitly holds every permission as well,
        // so permission-based UI checks resolve without the Gate::before bypass.
        app(Role::class)
            ->where('name', 'super admin')
            ->first()
            ->syncPermissions(Permission::pluck('name')->all());
    }

    /** @return array<int, array{name: string, guard_name: string}> */
    private function permissionRecords(): array
    {
        $permissions = collect(self::ROLE_PERMISSIONS)
            ->flatMap(static fn (array $permissions) => $permissions)
            ->unique()
            ->values();

        return $permissions
            ->map(static fn (string $name) => ['name' => $name, 'guard_name' => 'web'])
            ->all();
    }
}
