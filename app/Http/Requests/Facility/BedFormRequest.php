<?php

namespace App\Http\Requests\Facility;

use App\Enums\BedStatus;
use App\Models\Bed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-045: bed validation (ward assignment, per-ward number, manual status).
 * The bed number is unique per ward, not globally.
 */
class BedFormRequest extends FormRequest
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
        $bed = $this->route('bed');
        $isCreate = ! $bed instanceof Bed;

        $manualStatuses = array_map(
            static fn (BedStatus $status): string => $status->value,
            BedStatus::manualStates(),
        );

        $wardId = $isCreate
            ? (string) $this->input('ward_id')
            : $bed->ward_id;

        return [
            'ward_id' => $isCreate
                ? ['required', 'string', 'exists:wards,id']
                : ['prohibited'],
            'number' => [
                'required',
                'string',
                'max:'.Bed::NUMBER_MAX_LENGTH,
                'alpha_dash',
                Rule::unique('beds', 'number')
                    ->where('ward_id', $wardId)
                    ->ignore($isCreate ? null : $bed->getKey()),
            ],
            'status' => ['required', 'string', 'in:'.implode(',', $manualStatuses)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ward_id' => $this->filled('ward_id') ? (string) $this->ward_id : null,
            'number' => $this->filled('number') ? mb_strtoupper(trim((string) $this->number)) : null,
            'status' => $this->filled('status') ? mb_strtolower(trim((string) $this->status)) : null,
        ]);
    }
}
