<?php

namespace Tests\Feature\Public;

use App\Enums\Sex;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Team;
use App\Models\TreatmentStatus;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class WardDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_ward_occupancy_is_projected_for_public_display(): void
    {
        Date::setTestNow(now()->setTime(12, 0, 0));

        $ward = Ward::query()->create([
            'code' => 'W-01',
            'name' => 'Emergency Ward',
            'capacity' => 12,
        ]);

        $bed = Bed::query()->create([
            'ward_id' => $ward->id,
            'number' => 'A1',
            'status' => 'occupied',
        ]);

        $patient = Patient::query()->create([
            'patient_code' => 'ABC1234',
            'fullname' => 'Jane Doe',
            'sex' => Sex::Female,
            'age' => 42,
        ]);

        $team = Team::query()->create([
            'name' => 'Cardiology',
            'status' => 'active',
        ]);

        $status = TreatmentStatus::query()->create([
            'slug' => 'observation',
            'label' => 'Observation',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Encounter::query()->create([
            'patient_id' => $patient->id,
            'ward_id' => $ward->id,
            'bed_id' => $bed->id,
            'team_id' => $team->id,
            'treatment_status_id' => $status->id,
            'arrived_at' => now()->subHours(4),
            'notes' => 'Public display should not reveal notes',
        ]);

        BedAllocation::query()->create([
            'bed_id' => $bed->id,
            'patient_id' => $patient->id,
            'actor_id' => null,
            'started_at' => now()->subHours(4),
            'reason' => 'admission',
            'notes' => 'Occupancy ledger',
        ]);

        $this->get(route('display.ward', ['ward' => $ward->id], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('display/ward')
                ->where('ward.code', 'W-01')
                ->where('occupants.0.patient_id_masked', 'ABC****')
                ->where('occupants.0.initials', 'JD')
                ->where('occupants.0.team', 'Cardiology')
                ->where('occupants.0.treatment_status', 'Observation')
                ->where('occupants.0.occupancy_status', 'Occupied'))
            ->assertDontSeeText('Public display should not reveal notes')
            ->assertDontSeeText('Jane Doe');
    }
}
