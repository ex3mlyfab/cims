<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserFormRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-042: user management (Administration nav group).
 * Authorization: `permission:users.*` route middleware + UserPolicy.
 */
class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->with('roles:id,name')
            ->orderBy('deactivated_at')
            ->orderBy('name')
            ->get()
            ->map(static function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->all(),
                    'deactivated' => $user->isDeactivated(),
                ];
            });

        return Inertia::render('users/index', ['users' => $users]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(UserFormRequest $request, UserService $service): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = $service->create($request->validated());
        $service->sendPasswordResetLink($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created and a password-set link was sent to their email.')]);

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->all(),
            ],
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(UserFormRequest $request, User $user, UserService $service): RedirectResponse
    {
        $this->authorize('update', $user);

        $service->update($user, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    public function deactivate(User $user, UserService $service): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $service->deactivate($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deactivated and their sessions were signed out.')]);

        return to_route('users.index');
    }

    public function reactivate(User $user, UserService $service): RedirectResponse
    {
        $this->authorize('deactivate', $user);

        $service->reactivate($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User reactivated.')]);

        return to_route('users.index');
    }

    public function sendResetLink(User $user, UserService $service): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $service->sendPasswordResetLink($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('A password-reset link was sent to their email.')]);

        return to_route('users.index');
    }

    /**
     * Assignable roles for the form UI. The `super admin` role appears only
     * for super admin grantors (server-side validation stays authoritative).
     *
     * @return list<array{id: string, name: string}>
     */
    protected function roleOptions(): array
    {
        $roles = Role::query()
            ->orderBy('name');

        /** @var User|null $actor */
        $actor = auth()->user();

        if (! $actor?->hasRole(Role::SUPER_ADMIN)) {
            $roles->where('name', '!=', Role::SUPER_ADMIN);
        }

        /** @var list<array{id: string, name: string}> $options */
        $options = $roles->get(['id', 'name'])
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->all();

        return $options;
    }
}
