<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

/*
 * CIMA-042: user management. Covers the permission boundary, deactivation
 * semantics (account + sessions, security rule 10), last-super-admin and
 * self-deactivation guards, password-set links, and the audit trail.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('hospital administrator');

        return $user;
    }

    /**
     * assertSessionHas callback: the Inertia flash bag carries an error toast.
     */
    protected function errorToast(): callable
    {
        return fn ($flash): bool => ($flash['toast']['type'] ?? '') === 'error';
    }

    protected function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super admin');

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->get(route('users.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_users_without_user_permissions_are_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $nurse = User::factory()->create();
        $nurse->assignRole('nurse');

        $this->actingAs($nurse)->get(route('users.index', absolute: false))->assertForbidden();

        $this->actingAs($nurse)
            ->post(route('users.store', absolute: false), [
                'name' => 'Sneaky User',
                'email' => 'sneaky@example.com',
                'roles' => [],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_administrator_can_create_a_user_and_a_password_set_link_is_sent(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('users.store', absolute: false), [
                'name' => 'Amara Okafor',
                'email' => 'amara@example.com',
                'roles' => ['nurse'],
            ])
            ->assertRedirect(route('users.index'));

        $created = User::query()->where('email', 'amara@example.com')->firstOrFail();
        $this->assertNull($created->password);
        $this->assertNull($created->deactivated_at);
        $this->assertTrue($created->hasRole('nurse'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $user->id,
            'action' => 'user.created',
            'record_id' => $created->id,
        ]);
    }

    public function test_create_validates_fields_and_role_grants(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('users.store', absolute: false), [
                'name' => 'Duplicate Email',
                'email' => $user->email,
                'roles' => ['nurse'],
            ])
            ->assertSessionHasErrors(['email']);

        $this->actingAs($user)
            ->post(route('users.store', absolute: false), [
                'name' => 'Fake Role',
                'email' => 'fake-role@example.com',
                'roles' => ['not-a-role'],
            ])
            ->assertSessionHasErrors(['roles.0']);

        // Only a super admin may grant the super admin role.
        $this->actingAs($user)
            ->post(route('users.store', absolute: false), [
                'name' => 'Escalation Attempt',
                'email' => 'escalation@example.com',
                'roles' => ['super admin'],
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('users', ['email' => 'escalation@example.com']);
    }

    public function test_super_admin_can_grant_the_super_admin_role(): void
    {
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);

        $actor = $this->superAdmin();

        $this->actingAs($actor)
            ->post(route('users.store', absolute: false), [
                'name' => 'Second Superset',
                'email' => 'second-super@example.com',
                'roles' => ['super admin'],
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(
            User::query()->where('email', 'second-super@example.com')->firstOrFail()->hasRole('super admin'),
        );
    }

    public function test_administrator_can_update_a_user(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $target->assignRole('nurse');

        $this->actingAs($actor)
            ->put(route('users.update', ['user' => $target->id], absolute: false), [
                'name' => 'Amara Okafor',
                'email' => $target->email,
                'roles' => ['records officer'],
            ])
            ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Amara Okafor', $target->name);
        $this->assertTrue($target->hasRole('records officer'));
        $this->assertFalse($target->hasRole('nurse'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'user.updated',
            'record_id' => $target->id,
        ]);
    }

    public function test_demoting_the_last_super_admin_is_refused(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $target->assignRole('super admin'); // the only active super admin

        $this->actingAs($actor)
            ->put(route('users.update', ['user' => $target->id], absolute: false), [
                'name' => $target->name,
                'email' => $target->email,
                'roles' => ['nurse'],
            ])
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', $this->errorToast());

        $this->assertTrue($target->refresh()->hasRole('super admin'));
    }

    public function test_update_validates_fields(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($actor)
            ->put(route('users.update', ['user' => $target->id], absolute: false), [
                'name' => str_repeat('n', 150),
                'email' => 'not-an-email',
                'roles' => [],
            ])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_deactivated_user_loses_access_and_sessions(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $target->assignRole('ward manager');

        // A live session exists for the target.
        DB::table('sessions')->insert([
            'id' => 'sess_target',
            'user_id' => $target->id,
            'payload' => base64_encode('x'),
            'last_activity' => time(),
        ]);

        $this->actingAs($actor)
            ->post(route('users.deactivate', ['user' => $target->id], absolute: false))
            ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertNotNull($target->deactivated_at);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $target->id)->count());

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'user.deactivated',
            'record_id' => $target->id,
        ]);

        // Actually signed in as a deactivated user: middleware logs out and
        // redirects to login before the route even runs.
        $this->actingAs($target->refresh())
            ->get(route('dashboard', absolute: false))
            ->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
    }

    public function test_deactivated_users_cannot_log_in(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->assignRole('nurse');
        $user->forceFill(['deactivated_at' => now()])->save();

        $response = $this->post(route('login.store', absolute: false), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_self_deactivation_is_refused(): void
    {
        $actor = $this->admin();

        $this->actingAs($actor)
            ->post(route('users.deactivate', ['user' => $actor->id], absolute: false))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', $this->errorToast());

        $this->assertNull($actor->refresh()->deactivated_at);
    }

    public function test_last_active_super_admin_cannot_be_deactivated(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();
        $target->assignRole('super admin'); // the only active super admin

        $this->actingAs($actor)
            ->post(route('users.deactivate', ['user' => $target->id], absolute: false))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', $this->errorToast());

        $this->assertNull($target->refresh()->deactivated_at);
    }

    public function test_administrator_can_reactivate_a_deactivated_user(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create(['deactivated_at' => now()]);

        $this->actingAs($actor)
            ->post(route('users.reactivate', ['user' => $target->id], absolute: false))
            ->assertRedirect(route('users.index'));

        $this->assertNull($target->fresh()->deactivated_at);

        $this->actingAs($actor)
            ->post(route('users.reactivate', ['user' => $target->id], absolute: false))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', $this->errorToast());
    }

    public function test_administrator_can_send_a_password_reset_link(): void
    {
        $actor = $this->admin();
        $target = User::factory()->create();

        Password::shouldReceive('sendResetLink')->once()
            ->with(['email' => $target->email])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->actingAs($actor)
            ->post(route('users.send-reset-link', ['user' => $target->id], absolute: false))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $actor->id,
            'action' => 'user.password_reset_requested',
            'record_id' => $target->id,
        ]);
    }

    public function test_users_can_be_set_up_without_a_password_via_the_reset_broker(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $service = app(UserService::class);
        $user = $service->create([
            'name' => 'No Password Yet',
            'email' => 'no-password@example.com',
            'roles' => [],
        ]);

        $service->sendPasswordResetLink($user);

        $this->assertNull($user->password);
        $this->assertDatabaseCount('password_reset_tokens', 1);
    }

    public function test_audit_log_is_append_only(): void
    {
        $log = AuditLog::query()->create([
            'actor_id' => null,
            'action' => 'test.action',
            'record_type' => 'system',
        ]);

        $this->assertSame(1, AuditLog::query()->count());

        $this->expectException(RuntimeException::class);
        $log->action = 'tampered.action';
        $log->save();
    }

    public function test_audit_log_deletion_is_refused(): void
    {
        $log = AuditLog::query()->create([
            'actor_id' => null,
            'action' => 'test.action',
            'record_type' => 'system',
        ]);

        $this->expectException(RuntimeException::class);
        $log->delete();
    }

    public function test_user_service_role_assignment_normalization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $roleNames = collect(Role::query()->where('name', '!=', 'super admin')->pluck('name')->all());

        $service = app(UserService::class);
        $user = $service->create([
            'name' => 'Many Roles',
            'email' => 'many-roles@example.com',
            'roles' => $roleNames->take(5)->values()->all(),
        ]);

        $this->assertCount(5, $user->roles);
    }
}
