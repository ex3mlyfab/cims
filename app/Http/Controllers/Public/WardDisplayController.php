<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use App\Models\Ward;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WardDisplayController extends Controller
{
    public function show(Ward $ward): Response
    {
        $occupants = Encounter::query()
            ->with(['patient', 'bed', 'team', 'treatmentStatus'])
            ->where('ward_id', $ward->getKey())
            ->whereNull('discharged_at')
            ->get()
            ->map(function (Encounter $encounter): array {
                $bed = $encounter->bed;
                $allocation = $bed ? $bed->activeAllocation()->first() : null;
                $startedAt = $allocation ? $allocation->started_at : ($encounter->arrived_at ?? now());
                $patient = $encounter->patient;
                $treatmentStatus = $encounter->treatmentStatus;
                $team = $encounter->team;

                if (! $startedAt instanceof CarbonInterface) {
                    $startedAt = Carbon::parse((string) $startedAt);
                }

                return [
                    'id' => $encounter->id,
                    'patient_id_masked' => $this->maskPatientCode((string) ($patient->patient_code ?? '')),
                    'initials' => $this->initials((string) ($patient->fullname ?? '')),
                    'bed_number' => $bed ? $bed->number : '',
                    'sex' => $patient->sex->label(),
                    'age' => (int) ($patient->age ?? 0),
                    'duration' => $this->durationLabel($startedAt),
                    'treatment_status' => $treatmentStatus->label ?? 'Unassigned',
                    'team' => $team->name ?? 'Unassigned',
                    'occupancy_status' => $bed->status->label(),
                ];
            })
            ->values();

        return Inertia::render('display/ward', [
            'ward' => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
            ],
            'occupants' => $occupants,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    protected function maskPatientCode(string $value): string
    {
        if ($value === '') {
            return '***';
        }

        $trimmed = trim($value);
        $visible = min(3, mb_strlen($trimmed));

        return mb_substr($trimmed, 0, $visible).str_repeat('*', max(0, mb_strlen($trimmed) - $visible));
    }

    protected function initials(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        $letters = is_array($parts) ? array_slice($parts, 0, 2) : [];

        return strtoupper(implode('', array_map(static fn (string $part): string => mb_substr($part, 0, 1), $letters)));
    }

    protected function durationLabel(Carbon|CarbonInterface $startedAt): string
    {
        $diff = now()->diff($startedAt);

        if ($diff->days > 0) {
            return sprintf('%dd %dh', $diff->days, $diff->h);
        }

        if ($diff->h > 0) {
            return sprintf('%dh %dm', $diff->h, $diff->i);
        }

        return sprintf('%dm', $diff->i);
    }
}
