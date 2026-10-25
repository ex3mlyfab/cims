<?php

namespace App\Services;

use App\Models\HospitalBranding;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * CIMA-040: hospital branding settings (ADR-005 singleton).
 *
 * Clears replaced logo assets and keeps the singleton semantics (one row)
 * for branding. Authorization is assumed handled by controller/route
 * middleware (`permission:settings.branding`), per cima-architecture.
 */
class HospitalBrandingService
{
    public const LOGO_DISK = 'public';

    public const LOGO_DIRECTORY = 'branding';

    /**
     * Persist branding values, replacing/removing the logo asset as needed.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(?HospitalBranding $branding, array $data, ?UploadedFile $logo = null): HospitalBranding
    {
        $branding ??= new HospitalBranding;

        $branding->name = trim($data['name'] ?? $branding->name);
        $branding->display_text = array_key_exists('display_text', $data)
            ? $data['display_text']
            : $branding->display_text;
        $branding->default_preset = $data['default_preset'] ?? $branding->defaultPreset();
        $branding->allowed_presets = array_key_exists('allowed_presets', $data)
            ? (filled($data['allowed_presets']) ? array_values($data['allowed_presets']) : null)
            : $branding->allowedPresets();

        if ($logo !== null) {
            $this->deleteLogo($branding);

            $stored = $logo->store(self::LOGO_DIRECTORY, self::LOGO_DISK);

            if ($stored === false) {
                throw new FileNotFoundException(__('The hospital logo could not be stored.'));
            }

            $branding->logo_path = $stored;
        } elseif ($data['remove_logo'] ?? false) {
            $this->deleteLogo($branding);
            $branding->logo_path = null;
        }

        $branding->save();

        return $branding->refresh();
    }

    /**
     * Remove the current logo asset from storage (no-op when absent).
     */
    protected function deleteLogo(?HospitalBranding $branding): void
    {
        if (! $branding?->logo_path) {
            return;
        }

        Storage::disk(self::LOGO_DISK)->delete($branding->logo_path);
    }
}
