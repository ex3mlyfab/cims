<?php

namespace Tests\Feature\Clinical;

use App\Models\TreatmentStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CIMA-052: treatment status CRUD + access checks.
 */
class TreatmentStatusManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function userWithRole(string $name): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($name);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->get(route('treatment-statuses.index', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_view_only_roles_can_list_but_not_manage_statuses(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (['doctor', 'nurse'] as $roleName) {
            $actor = User::factory()->create();
            $actor->assignRole($roleName);

            $this->actingAs($actor)
                ->get(route('treatment-statuses.index', absolute: false))
                ->assertOk();

            $this->actingAs($actor)
                ->post(route('treatment-statuses.store', absolute: false), [
                    'label' => 'Awaiting review',
                    'slug' => 'awaiting-review',
                    'sort_order' => 10,
                    'is_active' => true,
                ])
                ->assertForbidden();
        }

        $recordOfficer = User::factory()->create();
        $recordOfficer->assignRole('records officer');

        $this->actingAs($recordOfficer)
            ->get(route('treatment-statuses.index', absolute: false))
            ->assertForbidden();

        $this->assertDatabaseCount('treatment_statuses', 0);
    }

    public function test_hospital_administrator_can_manage_statuses(): void
    {
        $actor = $this->userWithRole('hospital administrator');

        $this->actingAs($actor)
            ->get(route('treatment-statuses.index', absolute: false))
            ->assertOk();

        $this->actingAs($actor)
            ->post(route('treatment-statuses.store', absolute: false), [
                'label' => 'Awaiting review',
                'slug' => 'awaiting-review',
                'sort_order' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('treatment-statuses.index'));

        $status = TreatmentStatus::query()->sole();
        $this->assertSame('Awaiting review', $status->label);
        $this->assertSame('awaiting-review', $status->slug);
        $this->assertTrue($status->is_active);

        $this->actingAs($actor)
            ->put(route('treatment-statuses.update', ['status' => $status->id], absolute: false), [
                'label' => 'Review in progress',
                'slug' => 'review-in-progress',
                'sort_order' => 15,
                'is_active' => false,
            ])
            ->assertRedirect(route('treatment-statuses.index'));

        $status->refresh();
        $this->assertSame('Review in progress', $status->label);
        $this->assertSame('review-in-progress', $status->slug);
        $this->assertSame(15, $status->sort_order);
        $this->assertFalse($status->is_active);

        $this->actingAs($actor)
            ->delete(route('treatment-statuses.destroy', ['status' => $status->id], absolute: false))
            ->assertRedirect(route('treatment-statuses.index'));

        $this->assertDatabaseMissing('treatment_statuses', ['id' => $status->id]);
    }

    public function test_duplicate_slug_and_label_are_rejected(): void
    {
        $actor = $this->userWithRole('hospital administrator');
        TreatmentStatus::query()->create([
            'label' => 'Awaiting review',
            'slug' => 'awaiting-review',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->post(route('treatment-statuses.store', absolute: false), [
                'label' => 'Awaiting review',
                'slug' => 'a-second-slug',
                'sort_order' => 11,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['label']);

        $this->actingAs($actor)
            ->post(route('treatment-statuses.store', absolute: false), [
                'label' => 'Another label',
                'slug' => 'awaiting-review',
                'sort_order' => 12,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['slug']);
    }
}
