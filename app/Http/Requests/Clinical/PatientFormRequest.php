<?php

namespace App\Http\Requests\Clinical;

use App\Enums\Sex;
use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-050: patient registration validation (fullname, patient code, sex,
 * age) — exactly the fields of the search-first modal in the
 * admission-record flow.
 */
class PatientFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware grants module access; the policy decides per record.
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => [
                'required',
                'string',
                'min:2',
                'max:'.Patient::FULLNAME_MAX_LENGTH,
            ],
            'patient_code' => [
                'required',
                'string',
                'max:'.Patient::CODE_MAX_LENGTH,
                'alpha_dash',
                Rule::unique('patients', 'patient_code'),
            ],
            'sex' => [
                'required',
                Rule::in(Sex::values()),
            ],
            'age' => [
                'required',
                'integer',
                'min:0',
                'max:'.Patient::MAX_AGE,
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fullname' => $this->filled('fullname') ? trim((string) $this->fullname) : null,
            'patient_code' => $this->filled('patient_code') ? mb_strtoupper(trim((string) $this->patient_code)) : null,
            'age' => $this->filled('age') ? (int) $this->age : null,
        ]);
    }
}
