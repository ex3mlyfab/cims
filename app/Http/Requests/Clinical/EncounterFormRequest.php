<?php

namespace App\Http\Requests\Clinical;

use App\Models\Bed;
use App\Models\Encounter;
use App\Models\Team;
use App\Models\Ward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * CIMA-051: admission create validation. The selected bed must belong to the
 * selected ward, and the team/treatment status must exist and remain active.
 * Ward scoping: accounts other than super admin / hospital administrator may
 * only admit into (and keep admissions in) wards they are assigned to.
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var \App\Models\User|null $user */
            $user = $this->user();

            if ($user === null || $user->hasFullWardAccess()) {
                return;
            }

            $encounter = $this->route('encounter');

            // Editing an admission that already sits in an out-of-scope ward
            // is refused outright — even moving it out requires the scope.
            if ($encounter instanceof Encounter && ! $user->canAccessWard($encounter->ward_id)) {
                $validator->errors()->add(
                    'ward_id',
                    __('You may only manage admissions within wards you are assigned to.'),
                );

                return;
            }

            $targetWardId = (string) $this->input('ward_id', $encounter instanceof Encounter ? $encounter->ward_id : '');

            if ($targetWardId !== '' && ! $user->canAccessWard($targetWardId)) {
                $validator->errors()->add(
                    'ward_id',
                    __('Patients may only be admitted into wards you are assigned to.'),
                );
            }
        });
    }
}
