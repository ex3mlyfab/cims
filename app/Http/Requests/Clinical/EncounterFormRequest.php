<?php

namespace App\Http\Requests\Clinical;

use App\Models\Bed;
use App\Models\Team;
use App\Models\Ward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-051: admission create validation. The selected bed must belong to the
 * selected ward, and the team/treatment status must exist and remain active.
 */
class EncounterFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'patient_id' => [
                Rule::requiredIf($this->isMethod('post') || $this->routeIs('encounters.store')),
                'exists:patients,id',
            ],
            'ward_id' => ['required', 'exists:wards,id'],
            'bed_id' => [
                'required',
                'exists:beds,id',
                Rule::exists('beds', 'id')->where(fn ($query) => $query->where('ward_id', $this->input('ward_id'))),
            ],
            'team_id' => ['required', 'exists:teams,id'],
            'treatment_status_id' => [
                'required',
                'exists:treatment_statuses,id',
                Rule::exists('treatment_statuses', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'arrived_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->notes) : null,
            'arrived_at' => $this->filled('arrived_at') ? $this->arrived_at : now()->toDateTimeString(),
        ]);
    }
}
