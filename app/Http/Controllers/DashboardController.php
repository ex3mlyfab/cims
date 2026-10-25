<?php

namespace App\Http\Controllers;

use App\Enums\BedStatus;
use App\Models\Bed;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canViewBeds = $user->can('beds.view');
        $canViewWards = $user->can('wards.view');
        $canViewPatients = $user->can('patients.view');
        $canManageEncounters = $user->can('encounters.manage');

        $bedsByWard = $canViewBeds
            ? Bed::query()
                ->select('ward_id')
                ->selectRaw('COUNT(*) AS installed_beds')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS occupied_beds', [BedStatus::Occupied->value])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS available_beds', [BedStatus::Available->value])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS cleaning_beds', [BedStatus::Cleaning->value])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS out_of_service_beds', [BedStatus::OutOfService->value])
                ->groupBy('ward_id')
                ->get()
                ->keyBy('ward_id')
            : collect();

        $totalBeds = (int) $bedsByWard->sum('installed_beds');
        $occupiedBeds = (int) $bedsByWard->sum('occupied_beds');

        $wards = $canViewBeds
            ? Ward::query()
                ->when(! $canViewWards, static fn ($query) => $query->whereHas('beds'))
                ->orderBy('code')
                ->get()
                ->map(function (Ward $ward) use ($bedsByWard): array {
                    $counts = $bedsByWard->get($ward->id);

                    return [
                        'id' => $ward->id,
                        'code' => $ward->code,
                        'name' => $ward->name,
                        'capacity' => $ward->capacity,
                        'installedBeds' => (int) ($counts?->installed_beds ?? 0),
                        'occupiedBeds' => (int) ($counts?->occupied_beds ?? 0),
                        'availableBeds' => (int) ($counts?->available_beds ?? 0),
                        'cleaningBeds' => (int) ($counts?->cleaning_beds ?? 0),
                        'outOfServiceBeds' => (int) ($counts?->out_of_service_beds ?? 0),
                    ];
                })
                ->values()
            : collect();

        $recentAdmissions = $canManageEncounters && $canViewPatients
            ? Encounter::query()
                ->active()
                ->with(['patient:id,fullname,patient_code', 'ward:id,code,name'])
                ->orderByDesc('arrived_at')
                ->limit(6)
                ->get()
                ->map(static fn (Encounter $encounter): array => [
                    'id' => $encounter->id,
                    'patient' => [
                        'fullname' => $encounter->patient->fullname,
                        'code' => $encounter->patient->patient_code,
                    ],
                    'ward' => [
                        'code' => $encounter->ward->code,
                        'name' => $encounter->ward->name,
                    ],
                    'arrivedAt' => $encounter->arrived_at?->toIso8601String(),
                    'arrivedLabel' => $encounter->arrived_at?->format('j M · H:i'),
                ])
                ->values()
            : collect();

        return Inertia::render('dashboard', [
            'stats' => [
                'activeInpatients' => $canManageEncounters ? Encounter::active()->count() : null,
                'admissionsToday' => $canManageEncounters
                    ? Encounter::query()->whereDate('arrived_at', today())->count()
                    : null,
                'patientRegistryCount' => $canViewPatients ? Patient::query()->count() : null,
                'installedBeds' => $canViewBeds ? $totalBeds : null,
                'occupiedBeds' => $canViewBeds ? $occupiedBeds : null,
                'availableBeds' => $canViewBeds ? (int) $bedsByWard->sum('available_beds') : null,
                'bedsBeingServiced' => $canViewBeds
                    ? (int) $bedsByWard->sum('cleaning_beds') + (int) $bedsByWard->sum('out_of_service_beds')
                    : null,
                'bedOccupancyRate' => $canViewBeds
                    ? ($totalBeds > 0 ? (int) round(($occupiedBeds / $totalBeds) * 100) : 0)
                    : null,
            ],
            'wards' => $wards,
            'recentAdmissions' => $recentAdmissions,
        ]);
    }
}
