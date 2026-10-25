<?php

namespace App\Services;

use App\Exceptions\AlreadyActive;
use App\Exceptions\LastSuperAdmin;
use App\Exceptions\SelfDeactivation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/**
 * CIMA-042: user lifecycle (create/update/deactivate/reactivate/reset link).
 *
 * Users are never hard-deleted (audit history preserved). Authorization is
 * enforced by route middleware + UserPolicy; this service holds the record
 * preconditions with typed domain exceptions:
 *  - no self-deactivation (lockout risk),
 *  - the last active super admin can never be deactivated or demoted,
 *  - deactivated users lose their sessions immediately (security rule 10).
 * Domain mutations run inside transactions and are audited (ADR-004).
 */
class UserService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {name: string, email: string, roles?: string[]}
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                // No admin-chosen passwords: the user sets their own via a
                // single-use password-set link (same mechanism as reset).
                'password' => null,
            ]);
            $user->save();

            $user->syncRoles($this->roleNames($data));

            $this->auditLogger->record(
                'user.created',
                $user,
                after: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles()->pluck('name')->all(),
                ],
            );

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data  shape: {name?: string, email?: string, roles?: string[]}
     */
    public function update(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $beforeRoles = $this->roleNamesOf($user);
            $before = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $beforeRoles,
            ];

            $nextRoles = isset($data['roles'])
                ? $this->roleNames($data)
                : $beforeRoles;
            $this->assertSuperAdminRemain($user, $nextRoles);

            if (isset($data['name']) && (string) $data['name'] !== $user->name) {
                $user->name = $data['name'];
            }

            if (isset($data['email']) && (string) $data['email'] !== $user->email) {
                $user->email = $data['email'];
            }

            $user->save();
            $user->syncRoles($nextRoles);

            $this->auditLogger->record('user.updated', $user, before: $before, after: [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name')->all(),
            ]);
        });
    }

    public function deactivate(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->assertActorSafety($user);
            $this->assertSuperAdminRemain($user, []);

            $user->forceFill(['deactivated_at' => now()])->save();

            // Security rule 10: no access may outlive deactivation.
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $this->auditLogger->record('user.deactivated', $user, after: [
                'deactivated_at' => $user->deactivated_at?->toISOString(),
                'sessions_destroyed' => true,
            ]);
        });
    }

    public function reactivate(User $user): void
    {
        DB::transaction(function () use ($user): void {
            if (! $user->isDeactivated()) {
                throw new AlreadyActive(__('This account is already active.'));
            }

            $user->forceFill(['deactivated_at' => null])->save();

            $this->auditLogger->record('user.reactivated', $user);
        });
    }

    /**
     * Admin never learns or sets the password; a single-use signed link
     * lets the user choose their own.
     */
    public function sendPasswordResetLink(User $user): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        $this->auditLogger->record('user.password_reset_requested', $user);

        return $status;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    protected function roleNames(array $data): array
    {
        return array_values(array_unique(array_filter((array) ($data['roles'] ?? []))));
    }

    /**
     * @return list<string>
     */
    protected function roleNamesOf(User $user): array
    {
        return array_values(array_map('strval', (array) $user->roles()->pluck('name')->all()));
    }

    protected function assertActorSafety(User $user): void
    {
        if ($user->id === auth()->id()) {
            throw new SelfDeactivation(__('You cannot deactivate your own account.'));
        }
    }

    /**
     * Guards role changes that would leave zero active super admin accounts
     * (ADR-002). `$nextRoles` is the proposed role set; empty means removal
     * of every role.
     *
     * @param  list<string>  $nextRoles
     */
    protected function assertSuperAdminRemain(User $user, array $nextRoles): void
    {
        if (! $user->hasRole(Role::SUPER_ADMIN)) {
            return;
        }

        $keepsSuperAdmin = $nextRoles === []
            ? false
            : in_array(Role::SUPER_ADMIN, $nextRoles, true);

        if ($keepsSuperAdmin) {
            return;
        }

        $otherActive = User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('deactivated_at')
            ->role(Role::SUPER_ADMIN)
            ->count();

        if ($otherActive === 0) {
            throw new LastSuperAdmin(
                __('At least one active super admin account must remain.'),
            );
        }
    }
}
