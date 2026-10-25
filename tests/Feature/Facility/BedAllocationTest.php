<?php

namespace Tests\Feature\Facility;

use App\Enums\BedStatus;
use App\Enums\Sex;
use App\Exceptions\BedInUse;
use App\Models\Bed;
use App\Models\Patient;
use App\Models\User;
use App\Models\Ward;
use App\Services\BedAllocationService;
use App\Services\BedService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BedAllocationTest extends TestCase
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

    protected function patient(string $patientCode = 'P-1000'): Patient
    {
        return Patient::query()->create([
            'patient_code' => $patientCode,
            'fullname' => 'Ada Stone',
            'sex' => Sex::Female,
            'age' => 37,
        ]);
    }

    public function test_a_bed_can_only_have_one_active_allocation(): void
    {
        $actor = User::factory()->create();
        $bed = app(BedService::class)->create($actor, $this->ward(), [
            'number' => 'A-01',
            'status' => 'available',
        ]);

        $patient = $this->patient();
        app(BedAllocationService::class)->create($actor, $bed->refresh(), $patient);

        $this->assertSame(BedStatus::Occupied, $bed->refresh()->status);

        $this->expectException(BedInUse::class);

        app(BedAllocationService::class)->create($actor, $bed->refresh(), $this->patient('P-2000'));
    }

    public function test_closing_an_allocation_marks_the_bed_cleaning_and_keeps_history(): void
    {
        $actor = User::factory()->create();
        $bed = app(BedService::class)->create($actor, $this->ward(), [
            'number' => 'A-02',
            'status' => 'available',
        ]);

        $allocation = app(BedAllocationService::class)->create($actor, $bed->refresh(), $this->patient());
        $this->assertTrue($allocation->wasRecentlyCreated);

        app(BedAllocationService::class)->close($actor, $allocation->refresh());

        $this->assertNotNull($allocation->refresh()->ended_at);
        $this->assertSame(BedStatus::Cleaning, $bed->refresh()->status);
        $this->assertDatabaseHas('bed_allocations', [
            'bed_id' => $bed->id,
            'patient_id' => $allocation->patient_id,
        ]);
    }

    public function test_the_database_unique_constraint_blocks_duplicate_active_allocations(): void
    {
        $actor = User::factory()->create();
        $bed = app(BedService::class)->create($actor, $this->ward(), [
            'number' => 'A-03',
            'status' => 'available',
        ]);

        app(BedAllocationService::class)->create($actor, $bed->refresh(), $this->patient('P-3000'));

        $this->expectException(QueryException::class);

        DB::table('bed_allocations')->insert([
            'id' => (string) str()->ulid(),
            'bed_id' => $bed->id,
            'patient_id' => $this->patient('P-3001')->id,
            'actor_id' => $actor->id,
            'started_at' => now(),
            'ended_at' => null,
            'reason' => 'race',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
