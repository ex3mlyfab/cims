<?php

namespace App\Http\Requests\Facility;

use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-043: team validation (name + status lifecycle).
 */
class TeamFormRequest extends FormRequest
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
        $team = $this->route('team');
        $ignoreId = $team instanceof Team ? $team->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('teams', 'name')->ignore($ignoreId)],
            'status' => ['required', 'string', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name') ? trim((string) $this->name) : null,
            'status' => $this->filled('status') ? trim((string) $this->status) : null,
        ]);
    }
}
