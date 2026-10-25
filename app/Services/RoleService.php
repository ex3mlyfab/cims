<?php

namespace App\Services;

use App\Exceptions\RoleInUse;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * CIMA-041 (+ CIMA-042 audit wiring): role management.
 *
 * Authorization is enforced by route middleware (`permission:roles.*`) and
 * `RolePolicy`; the service asserts record preconditions with typed
 * exceptions. The seeded Super Admin role can never be mutated or deleted
 * here (defense-in-depth behind RolePolicy, ADR-002).
 * Mutations run inside transactions and are audited via AuditLogger.
 */
class RoleService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {name: string, permissions: string[]}
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = new Role;
            $role->name = (string) $data['name'];
            $role->guard_name = 'web';
            $role->save();

            $role->syncPermissions(array_values((array) ($data['permissions'] ?? [])));

            $this->auditLogger->record('role.created', $role, after: [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->all(),
            ]);

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data  shape: {name?: string, permissions: string[]}
     */
    public function update(Role $role, array $data): void
    {
        DB::transaction(function () use ($role, $data): void {
            $this->assertMutable($role);

            $before = [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->all(),
            ];

            if (isset($data['name']) && (string) $data['name'] !== $role->name) {
                $role->name = (string) $data['name'];
            }

            $role->syncPermissions(array_values((array) ($data['permissions'] ?? [])));
            $role->save();

            $this->auditLogger->record('role.updated', $role, before: $before, after: [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->all(),
            ]);
        });
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $this->assertMutable($role);

            if ($role->users()->count() > 0) {
                throw new RoleInUse(
                    __('The :role role is still assigned to users and cannot be deleted.', ['role' => $role->name]),
                );
            }

            $before = [
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->all(),
            ];

            $role->syncPermissions([]);
            $role->delete();

            $this->auditLogger->record('role.deleted', $role, before: $before);
        });
    }

    protected function assertMutable(Role $role): void
    {
        if ($role->isSuperAdmin()) {
            throw new RoleInUse(__('The super admin role is protected and cannot be changed.'));
        }
    }
}
