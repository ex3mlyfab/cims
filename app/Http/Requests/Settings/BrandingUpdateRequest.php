<?php

namespace App\Http\Requests\Settings;

use App\Support\ThemePresets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CIMA-040: hospital branding update validation (ADR-005 form + asset checks).
 */
class BrandingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by route middleware (`permission:settings.branding`).
        return (bool) $this->user();
    }

    /**
     * @return array<string, list<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'display_text' => ['nullable', 'string', 'max:120'],
            'default_preset' => ['required', 'string', Rule::in(ThemePresets::IDS)],
            'allowed_presets' => ['nullable', 'array'],
            'allowed_presets.*' => ['string', Rule::in(ThemePresets::IDS)],
            // Logo asset validation: web image formats only, 512 KB max.
            // The logo renders through <img>, so scripts in SVG assets are inert.
            'logo' => [
                'nullable',
                'file',
                'mimes:png,jpg,jpeg,webp,svg',
                'extensions:png,jpg,jpeg,webp,svg',
                'max:512',
            ],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => 'The logo must be a PNG, JPG, WEBP or SVG image.',
            'logo.extensions' => 'The logo must be a PNG, JPG, WEBP or SVG file.',
            'logo.max' => 'The logo may not be larger than 512 KB.',
            'default_preset.in' => 'The default theme must be one of the eight CIMA palettes.',
            'allowed_presets.*.in' => 'Allowed themes must be among the eight CIMA palettes.',
        ];
    }
}
