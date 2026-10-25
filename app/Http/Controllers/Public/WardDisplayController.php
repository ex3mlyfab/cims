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
                $allocation = $encounter->bed?->activeAllocation()->first();
                $startedAt = $allocation?->started_at ?? $encounter->arrived_at ?? now();

                return [
                    'id' => $encounter->id,
                    'patient_id_masked' => $this->maskPatientCode((string) ($encounter->patient?->patient_code ?? '')),
                    'initials' => $this->initials($encounter->patient?->fullname ?? ''),
                    'bed_number' => $encounter->bed?->number ?? '',
                    'sex' => $encounter->patient?->sex?->label() ?? '',
                    'age' => (int) ($encounter->patient?->age ?? 0),
                    'duration' => $this->durationLabel($startedAt),
                    'treatment_status' => $encounter->treatmentStatus?->label ?? 'Unassigned',
                    'team' => $encounter->team?->name ?? 'Unassigned',
                    'occupancy_status' => $encounter->bed?->status?->label() ?? 'Unknown',
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
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?? [];
        $letters = array_slice($parts, 0, 2);

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
