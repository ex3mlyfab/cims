<?php

namespace App\Models;

use App\Support\ThemePresets;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * CIMA-040: hospital branding (ADR-005 singleton settings row).
 *
 * The hospital-level defaults of the theme engine: name, logo, display
 * text and the default/allowed presets. This never *silently* overwrites
 * user appearance choices — user preferences take precedence.
 *
 * @property string $id
 * @property string $name
 * @property string|null $logo_path
 * @property string|null $display_text
 * @property string $default_preset
 * @property array<int, string>|null $allowed_presets
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HospitalBranding extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'logo_path',
        'display_text',
        'default_preset',
        'allowed_presets',
    ];

    protected $table = 'hospital_branding';

    protected function casts(): array
    {
        return [
            'allowed_presets' => 'array',
        ];
    }

    /**
     * The singleton settings row, or null before the first save.
     */
    public static function resolve(): ?self
    {
        return static::query()->first();
    }

    /**
     * Request-safe singleton lookup: never breaks pages (login, health)
     * that run before the branding table is migrated.
     */
    public static function resolveForRequest(): ?self
    {
        static $tablePresent = null;

        if ($tablePresent === null) {
            $tablePresent = Schema::hasTable((new self)->getTable());
        }

        if (! $tablePresent) {
            return null;
        }

        return static::resolve();
    }

    /**
     * Hospital name with fallback to the application name.
     */
    public static function resolveName(): string
    {
        $branding = static::resolve();

        return $branding->name ?? (string) config('app.name', 'Laravel');
    }

    /**
     * Public URL of the uploaded logo asset, when present.
     */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Preset identities the hospital allows users to choose (null = all).
     *
     * @return list<string>|null
     */
    public function allowedPresets(): ?array
    {
        $presets = $this->allowed_presets;

        if ($presets === null || $presets === []) {
            return null;
        }

        // Normalise against known presets so unknown stale entries resolve
        // to "all" rather than accidentally locking users out of the selector.
        $known = array_values(array_intersect($presets, ThemePresets::IDS));

        return $known === [] ? null : $known;
    }

    /**
     * Hospital default theme preset (falls back to the built-in default).
     */
    public function defaultPreset(): string
    {
        return in_array($this->default_preset, ThemePresets::IDS, true)
            ? $this->default_preset
            : ThemePresets::DEFAULT;
    }

    protected static function booted(): void
    {
        static::creating(static function (self $branding): void {
            if (blank($branding->default_preset)) {
                $branding->default_preset = ThemePresets::DEFAULT;
            }

            if (! $branding->allowed_presets) {
                $branding->allowed_presets = null;
            }
        });

        static::deleting(static function (self $branding): void {
            if ($branding->logo_path) {
                Storage::disk('public')->delete($branding->logo_path);
            }
        });
    }
}
