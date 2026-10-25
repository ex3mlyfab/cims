<?php

namespace Tests\Feature\Facility;

use App\Enums\BedStatus;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\BedEvent;
use App\Models\User;
use App\Models\Ward;
use App\Services\BedService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/*
 * CIMA-046: append-only bed_events occupation history. Covers event
 * writing inside BedService transactions (created / status_changed /
 * number_changed), append-only enforcement (update/delete refused), the
 * delete guard against beds with history, and actor + timestamp integrity.
 */
class BedHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function ward(): Ward
    {
        return Ward::query()->create([
            'code' => 'ED-A',
            'name' => 'Emergency observation',
            'capacity' => 4,
        ]);
    }

    protected function bed(Ward $ward): Bed
    {
        // Create through the service so the bed carries its bed.created
        // event, exactly like the production flow.
        app(BedService::class)->create(User::factory()->create(), $ward, [
            'number' => 'A-01',
            'status' => 'available',
        ]);

        return $ward->beds()->firstOrFail();
    }

    public function test_service_writes_created_event_with_actor_and_timestamp(): void
    {
        $actor = User::factory()->create();
        $ward = $this->ward();

        app(BedService::class)->create($actor, $ward, [
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $bed = $ward->beds()->firstOrFail();
        $event = $bed->events()->sole();

        $this->assertSame('bed.created', $event->action);
        $this->assertSame($actor->id, $event->actor_id);
        $this->assertTrue($event->actor->is($actor));
        $this->assertNotNull($event->occurred_at);
        $this->assertSame('A-01', $event->details['number']);
        $this->assertSame('available', $event->details['status']);
    }

    public function test_status_and_number_changes_are_recorded_only_when_they_change(): void
    {
        $actor = User::factory()->create();
        $bed = $this->bed($this->ward());

        app(BedService::class)->update($actor, $bed, [
            'number' => 'A-02',
            'status' => 'cleaning',
        ]);

        $this->assertSame(
            ['bed.created', 'bed.status_changed', 'bed.number_changed'],
            $bed->events()->orderBy('occurred_at')->orderBy('id')->pluck('action')->all(),
        );
        $statusEvent = $bed->events()->where('action', 'bed.status_changed')->sole();
        $numberEvent = $bed->events()->where('action', 'bed.number_changed')->sole();

        $this->assertSame(
            ['from' => 'available', 'to' => 'cleaning'],
            $statusEvent->details,
        );
        $this->assertSame(
            ['from' => 'A-01', 'to' => 'A-02'],
            $numberEvent->details,
        );
        $this->assertSame($actor->id, $statusEvent->actor_id);
        $this->assertSame($actor->id, $numberEvent->actor_id);

        // A no-op update must not append history noise.
        $before = $bed->events()->count();
        app(BedService::class)->update($actor, $bed->refresh(), [
            'number' => 'A-02',
            'status' => 'cleaning',
        ]);

        $this->assertSame($before, $bed->events()->count());
    }

    public function test_history_is_append_only(): void
    {
        $bed = $this->bed($this->ward());
        $event = BedEvent::query()->create([
            'bed_id' => $bed->id,
            'action' => 'bed.created',
            'details' => ['number' => 'A-01'],
            'occurred_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        $event->update(['action' => 'tampered']);
    }

    public function test_history_cannot_be_deleted(): void
    {
        $bed = $this->bed($this->ward());
        $event = BedEvent::query()->create([
            'bed_id' => $bed->id,
            'action' => 'bed.created',
            'occurred_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);

        $event->delete();
    }

    public function test_service_refuses_to_delete_a_bed_with_history(): void
    {
        $actor = User::factory()->create();
        $bed = $this->bed($this->ward());

        app(BedService::class)->update($actor, $bed->refresh(), [
            'number' => 'A-01',
            'status' => 'cleaning',
        ]);

        $this->expectException(BedInUse::class);

        app(BedService::class)->destroy($actor, $bed->refresh());
    }

    public function test_http_delete_of_a_used_bed_shows_the_history_error(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = User::factory()->create();
        $actor->assignRole('ward manager');
        $bed = $this->bed($this->ward());

        $this->actingAs($actor)
            ->put(route('beds.update', ['bed' => $bed->id], absolute: false), [
                'number' => 'A-01',
                'status' => 'cleaning',
            ])
            ->assertRedirect();

        $fresh = $bed->refresh();
        $fresh->status = BedStatus::Available; // free, but with history
        $fresh->saveQuietly();

        $this->actingAs($actor)
            ->delete(route('beds.destroy', ['bed' => $fresh->id], absolute: false))
            ->assertRedirect()
            ->assertSessionHas('inertia.flash_data', fn ($flash): bool => ($flash['toast']['type'] ?? '') === 'error');

        // CIMA-047: the dedicated history page presents the append-only
        // events newest first; the edit page is form-only now.
        $this->actingAs($actor)
            ->get(route('beds.show', ['bed' => $fresh->id], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('beds/show')
                ->has('history', 2)); // bed.created + bed.status_changed

        $this->actingAs($actor)
            ->get(route('beds.edit', ['bed' => $fresh->id], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('history'));
    }

    public function test_history_page_is_readable_with_view_permission_only(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // The doctor role carries beds.view but not beds.manage.
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');
        $bed = $this->bed($this->ward());

        $this->actingAs($doctor)
            ->get(route('beds.show', ['bed' => $bed->id], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('beds/show')
                ->has('bed.id')
                ->has('history'));

        // Management routes stay closed to view-only roles.
        $this->actingAs($doctor)
            ->get(route('beds.edit', ['bed' => $bed->id], absolute: false))
            ->assertForbidden();
    }
}
