<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * CIMA-022 / CIMA-023: idempotent roles & permissions seed.
 *
 * Re-running is safe: permissions and roles are created with firstOrCreate,
 * and role assignments are re-synced rather than duplicated.
 * Initial matrix = ADR-002 + docs/IMPLEMENTATION_PLAN.md §4; refine with stakeholders.
 * The 'super admin' role record itself is protected: it is never renamed,
 * re-seeded with other values, or deletable (CIMA-041 guards; ADR-002).
 */
class RolePermissionSeeder extends Seeder
{
    /** @var array<string, list<string>> role => permissions (empty list = dashboard only for now) */
    public const ROLE_PERMISSIONS = [
        'super admin' => [], // granted everything via Gate::before (ADR-002)
        'team manager' => [
            'teams.view', 'teams.manage',
        ],
        'hospital administrator' => [
            'users.view', 'users.create', 'users.update', 'users.deactivate', 'users.reset-password',
            'roles.view', 'permissions.view',
            'roles.create', 'roles.update', 'roles.delete',
            'wards.view', 'wards.manage',
            'beds.view', 'beds.manage',
            'teams.view', 'teams.manage',
            'statuses.view', 'statuses.manage',
            'settings.branding',
            'audit.view',
        ],
        'emergency department manager' => [
            'patients.view', 'patients.create', 'encounters.manage',
            'statuses.view', 'statuses.manage',
            'beds.view', 'beds.allocate', 'beds.transfer',
            'audit.view',
        ],
        'doctor' => [
            'patients.view', 'encounters.manage',
            'statuses.view',
            'beds.view', 'beds.transfer',
        ],
        'nurse' => [
            'patients.view', 'patients.create', 'encounters.manage',
            'statuses.view',
            'beds.view', 'beds.allocate', 'beds.transfer',
        ],
        'ward manager' => [
            'wards.view', 'wards.manage', 'beds.view', 'beds.manage',
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

        // Upsert bypasses model events, so the 24-hour permission cache
        // would serve stale contents on environments that already ran an
        // earlier seed (CIMA-041: new permissions must resolve immediately).
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        // The permission matrix is the single source of truth: drop any
        // previously-seeded permission that no longer appears in it (e.g.
        // modules retired from the plan), detaching roles/users first.
        $defined = collect(self::ROLE_PERMISSIONS)
            ->flatMap(static fn (array $permissions) => $permissions)
            ->unique();

        Permission::query()
            ->whereNotIn('name', $defined->all())
            ->get()
            ->each(static function (Permission $permission): void {
                $permission->roles()->detach();
                DB::table('model_has_permissions')
                    ->where('permission_id', $permission->getKey())
                    ->delete();
                $permission->delete();
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
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
