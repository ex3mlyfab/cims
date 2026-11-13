<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserFormRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
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
            ->with('roles:id,name', 'wards:id,name')
            ->orderBy('deactivated_at')
            ->orderBy('name')
            ->get()
            ->map(static function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->all(),
                    'wards' => $user->wards->pluck('name')->all(),
                    'deactivated' => $user->isDeactivated(),
                ];
            });

        return Inertia::render('users/index', ['users' => $users]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roleOptions' => $this->roleOptions(),
            'wardOptions' => $this->wardOptions(),
        ]);
    }

    public function store(UserFormRequest $request, UserService $service): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $user = $service->create($data);

        if (blank($data['password'] ?? null)) {
            $service->sendPasswordResetLink($user);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('User created and a password-set link was sent to their email.')]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);
        }

        return to_route('users.index');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->all(),
                'wards' => $user->wardIds(),
            ],
            'roleOptions' => $this->roleOptions(),
            'wardOptions' => $this->wardOptions(),
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

        $status = $service->sendPasswordResetLink($user);

        if ($status === Password::RESET_LINK_SENT) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('A password-reset link was sent to their email.')]);
        } else {
            Inertia::flash('toast', ['type' => 'error', 'message' => __($status)]);
        }

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

    /**
     * Assignable wards for the form UI (multi-select). Assigning wards scopes
     * ward visibility and admissions for every role except `super admin` and
     * `hospital administrator`.
     *
     * @return list<array{id: string, code: string, name: string}>
     */
    protected function wardOptions(): array
    {
        /** @var list<array{id: string, code: string, name: string}> $options */
        $options = Ward::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(static fn (Ward $ward): array => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
            ])
            ->all();

        return $options;
    }
}
