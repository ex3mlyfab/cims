<?php

namespace App\Http\Requests\Facility;

use App\Models\Ward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-044: ward validation (code, name, declared bed capacity).
 */
class WardFormRequest extends FormRequest
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
        $ward = $this->route('ward');
        $ignoreId = $ward instanceof Ward ? $ward->getKey() : null;

        return [
            'code' => [
                'required',
                'string',
                'max:'.Ward::CODE_MAX_LENGTH,
                'alpha_dash',
                Rule::unique('wards', 'code')->ignore($ignoreId),
            ],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('wards', 'name')->ignore($ignoreId),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:'.Ward::MAX_CAPACITY],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? mb_strtoupper(trim((string) $this->code)) : null,
            'name' => $this->filled('name') ? trim((string) $this->name) : null,
            'capacity' => $this->filled('capacity') ? (int) $this->capacity : null,
        ]);
    }
}
