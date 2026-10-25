<?php

namespace App\Http\Requests\Clinical;

use App\Models\TreatmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-052: treatment-status registry validation.
 */
class TreatmentStatusFormRequest extends FormRequest
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
        $status = $this->route('status');
        $ignoreId = $status instanceof TreatmentStatus ? $status->getKey() : null;

        return [
            'label' => [
                'required',
                'string',
                'min:2',
                'max:'.TreatmentStatus::LABEL_MAX_LENGTH,
                Rule::unique('treatment_statuses', 'label')->ignore($ignoreId),
            ],
            'slug' => [
                'required',
                'string',
                'max:'.TreatmentStatus::SLUG_MAX_LENGTH,
                'alpha_dash',
                Rule::unique('treatment_statuses', 'slug')->ignore($ignoreId),
            ],
            'sort_order' => [
                'required',
                'integer',
                'min:1',
                'max:'.TreatmentStatus::MAX_SORT_ORDER,
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => $this->filled('label') ? trim((string) $this->label) : null,
            'slug' => $this->filled('slug') ? mb_strtolower(trim((string) $this->slug)) : null,
            'sort_order' => $this->filled('sort_order') ? (int) $this->sort_order : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
