<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleFormRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-041: roles & permissions UI (Administration nav group).
 * Authorization: `permission:roles.*` route middleware + RolePolicy record rules.
 */
class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissionsCount' => $role->permissions_count,
                'usersCount' => $role->users_count,
                'superAdmin' => $role->isSuperAdmin(),
            ]);

        return Inertia::render('roles/index', ['roles' => $roles]);
    }

    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'groupedPermissions' => $this->groupedPermissions(),
        ]);
    }

    public function store(RoleFormRequest $request, RoleService $service): RedirectResponse
    {
        $service->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created.')]);

        return to_route('roles.index');
    }

    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('name')->all(),
            ],            'groupedPermissions' => $this->groupedPermissions(),
        ]);
    }

    public function update(RoleFormRequest $request, Role $role, RoleService $service): RedirectResponse
    {
        $this->authorize('update', $role);

        $service->update($role, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated.')]);

        return to_route('roles.index');
    }

    public function destroy(Role $role, RoleService $service): RedirectResponse
    {
        $this->authorize('delete', $role);

        $service->delete($role);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted.')]);

        return to_route('roles.index');
    }

    /**
     * All registry permissions grouped by module prefix (e.g. `beds.*`
     * under "beds") for the checkbox UI.
     *
     * @return array<string, array<int, array{id: int|string, name: string}>>
     */
    protected function groupedPermissions(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(static function (Permission $permission): string {
                $name = $permission->name;

                return str_contains($name, '.') ? (string) str($name)->before('.') : $name;
            })
            ->map(static fn ($permissions) => $permissions->map(static fn (Permission $permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
            ])->values()->all())
            ->all();
    }
}
