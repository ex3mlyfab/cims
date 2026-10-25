<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinical\PatientFormRequest;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-050: patient search & registration (Clinical Operations group).
 * Search-first module: the index filters by fullname / patient code; the
 * "no match" path opens an inline modal that registers the patient with
 * exactly those four fields. Duplicate detection is the search itself
 * (R5, manual reconciliation) plus DB-backed code uniqueness.
 */
class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $searchQuery = trim((string) $request->query('q', ''));

        $patients = Patient::query()
            ->search($searchQuery)
            ->orderBy('fullname')
            ->orderBy('patient_code')
            ->limit(100)
            ->get()
            ->map(static fn (Patient $patient): array => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'fullname' => $patient->fullname,
                'sex' => $patient->sex->value,
                'sex_label' => $patient->sex->label(),
                'age' => $patient->age,
            ]);

        return Inertia::render('patients/index', [
            'search_query' => $searchQuery,
            'patients' => $patients,
        ]);
    }

    public function store(PatientFormRequest $request, PatientService $service): RedirectResponse
    {
        $this->authorize('create', Patient::class);

        $service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Patient registered.')]);

        return to_route('patients.index');
    }
}
