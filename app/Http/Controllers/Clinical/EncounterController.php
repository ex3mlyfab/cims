<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinical\EncounterFormRequest;
use App\Models\Bed;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Team;
use App\Models\TreatmentStatus;
use App\Models\Ward;
use App\Services\EncounterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-051: admission record create flow. The patient search step lands here
 * with a selected patient id, then the form chooses ward + bed + team + status.
 */
class EncounterController extends Controller
{
    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $wardId = $request->query('ward_id');
        $teamId = $request->query('team_id');
        $statusId = $request->query('treatment_status_id');
        $from = $request->query('from');
        $to = $request->query('to');

        $encounters = Encounter::query()
            ->with(['patient', 'ward', 'team', 'treatmentStatus'])
            ->when($query !== '', function ($builder) use ($query) {
                $term = '%'.$query.'%';

                return $builder->where(function ($inner) use ($term, $query) {
                    $inner->whereHas('patient', function ($patientQuery) use ($term, $query) {
                        $patientQuery->where('fullname', 'like', $term)
                            ->orWhere('patient_code', 'like', $term);
                    })->orWhere('notes', 'like', $term);

                    if (preg_match('/^[A-Za-z0-9-]+$/', $query)) {
                        $inner->orWhere('id', 'like', '%'.$query.'%');
                    }
                });
            })
            ->when($wardId !== null && $wardId !== '', fn ($builder) => $builder->where('ward_id', $wardId))
            ->when($teamId !== null && $teamId !== '', fn ($builder) => $builder->where('team_id', $teamId))
            ->when($statusId !== null && $statusId !== '', fn ($builder) => $builder->where('treatment_status_id', $statusId))
            ->when($from !== null && $from !== '', fn ($builder) => $builder->whereDate('arrived_at', '>=', $from))
            ->when($to !== null && $to !== '', fn ($builder) => $builder->whereDate('arrived_at', '<=', $to))
            ->orderByDesc('arrived_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        return Inertia::render('encounters/index', [
            'query' => $query,
            'filters' => [
                'ward_id' => $wardId,
                'team_id' => $teamId,
                'treatment_status_id' => $statusId,
                'from' => $from,
                'to' => $to,
            ],
            'encounters' => $encounters->through(static fn (Encounter $encounter): array => [
                'id' => $encounter->id,
                'patient' => [
                    'id' => $encounter->patient->id,
                    'fullname' => $encounter->patient->fullname,
                    'patient_code' => $encounter->patient->patient_code,
                ],
                'ward' => [
                    'id' => $encounter->ward->id,
                    'code' => $encounter->ward->code,
                    'name' => $encounter->ward->name,
                ],
                'team' => [
                    'id' => $encounter->team->id,
                    'name' => $encounter->team->name,
                ],
                'treatment_status' => [
                    'id' => $encounter->treatmentStatus->id,
                    'label' => $encounter->treatmentStatus->label,
                ],
                'arrived_at' => $encounter->arrived_at?->format(DATE_ATOM),
                'discharged_at' => $encounter->discharged_at?->format(DATE_ATOM),
                'is_active' => $encounter->discharged_at === null,
                'notes' => $encounter->notes,
            ])->all(),
            'wards' => Ward::query()->orderBy('code')->get()->map(static fn (Ward $ward): array => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
            ])->all(),
            'teams' => Team::query()->orderBy('name')->get()->map(static fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
            ])->all(),
            'treatment_statuses' => TreatmentStatus::query()->orderBy('sort_order')->orderBy('label')->get()->map(static fn (TreatmentStatus $status): array => [
                'id' => $status->id,
                'label' => $status->label,
            ])->all(),
            'pagination' => [
                'current_page' => $encounters->currentPage(),
                'last_page' => $encounters->lastPage(),
                'total' => $encounters->total(),
                'links' => $encounters->links(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $patient = null;
        $selectedPatientId = $request->query('patient_id');

        if ($selectedPatientId !== null && $selectedPatientId !== '') {
            $patient = Patient::query()->find($selectedPatientId);
        }

        $wards = Ward::query()
            ->orderBy('code')
            ->get()
            ->map(static fn (Ward $ward): array => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
            ]);

        $teams = Team::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
            ]);

        $treatmentStatuses = TreatmentStatus::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(static fn (TreatmentStatus $status): array => [
                'id' => $status->id,
                'label' => $status->label,
                'slug' => $status->slug,
            ]);

        $availableBeds = Bed::query()
            ->with('ward')
            ->where('status', 'available')
            ->orderBy('ward_id')
            ->orderBy('number')
            ->get()
            ->map(static fn (Bed $bed): array => [
                'id' => $bed->id,
                'ward_id' => $bed->ward_id,
                'ward_code' => $bed->ward?->code,
                'number' => $bed->number,
                'label' => $bed->ward?->code.' · '.$bed->number,
            ]);

        return Inertia::render('encounters/create', [
            'patient' => $patient ? [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'fullname' => $patient->fullname,
                'sex' => $patient->sex->value,
                'age' => $patient->age,
            ] : null,
            'wards' => $wards,
            'teams' => $teams,
            'treatment_statuses' => $treatmentStatuses,
            'available_beds' => $availableBeds,
        ]);
    }

    public function store(EncounterFormRequest $request, EncounterService $service): RedirectResponse
    {
        $this->authorize('create', Encounter::class);

        $service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Admission created.')]);

        return to_route('patients.index');
    }

    public function edit(Encounter $encounter): Response
    {
        $this->authorize('update', $encounter);

        $wards = Ward::query()->orderBy('code')->get()->map(static fn (Ward $ward): array => [
            'id' => $ward->id,
            'code' => $ward->code,
            'name' => $ward->name,
        ]);

        $teams = Team::query()->orderBy('name')->get()->map(static fn (Team $team): array => [
            'id' => $team->id,
            'name' => $team->name,
        ]);

        $treatmentStatuses = TreatmentStatus::query()->where('is_active', true)->orderBy('sort_order')->orderBy('label')->get()->map(static fn (TreatmentStatus $status): array => [
            'id' => $status->id,
            'label' => $status->label,
            'slug' => $status->slug,
        ]);

        $availableBeds = Bed::query()->with('ward')->whereIn('status', ['available', 'occupied'])->orderBy('ward_id')->orderBy('number')->get()->map(static fn (Bed $bed): array => [
            'id' => $bed->id,
            'ward_id' => $bed->ward_id,
            'ward_code' => $bed->ward?->code,
            'number' => $bed->number,
            'label' => $bed->ward?->code.' · '.$bed->number,
        ]);

        return Inertia::render('encounters/edit', [
            'encounter' => [
                'id' => $encounter->id,
                'patient_id' => $encounter->patient_id,
                'ward_id' => $encounter->ward_id,
                'bed_id' => $encounter->bed_id,
                'team_id' => $encounter->team_id,
                'treatment_status_id' => $encounter->treatment_status_id,
                'notes' => $encounter->notes,
                'arrived_at' => $encounter->arrived_at?->toDateTimeString(),
            ],
            'patient' => [
                'id' => $encounter->patient->id,
                'patient_code' => $encounter->patient->patient_code,
                'fullname' => $encounter->patient->fullname,
                'sex' => $encounter->patient->sex->value,
                'age' => $encounter->patient->age,
            ],
            'wards' => $wards,
            'teams' => $teams,
            'treatment_statuses' => $treatmentStatuses,
            'available_beds' => $availableBeds,
        ]);
    }

    public function update(EncounterFormRequest $request, Encounter $encounter, EncounterService $service): RedirectResponse
    {
        $this->authorize('update', $encounter);

        $service->update($request->user(), $encounter, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Admission updated.')]);

        return to_route('patients.index');
    }

    public function discharge(Request $request, Encounter $encounter, EncounterService $service): RedirectResponse
    {
        $this->authorize('discharge', $encounter);

        $service->discharge($request->user(), $encounter, [
            'notes' => $request->input('notes'),
            'discharged_at' => $request->input('discharged_at'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient discharged.')]);

        return to_route('patients.index');
    }
}
