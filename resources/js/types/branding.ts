import type { ThemePresetId } from '@/lib/themes';

/**
 * CIMA-040: hospital branding (ADR-005) as shared by every Inertia page.
 * `defaultPreset`/`allowedPresets` are the hospital-level theme defaults;
 * user choices on top of these are never silently overwritten.
 */
export type HospitalBranding = {
    name: string | null;
    logoUrl: string | null;
    defaultPreset: ThemePresetId | null;
    allowedPresets: ThemePresetId[] | null;
};

/** Full branding record for the settings form. */
export type HospitalBrandingForm = HospitalBranding & {
    displayText: string | null;
};
