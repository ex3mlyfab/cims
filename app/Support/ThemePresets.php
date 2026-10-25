<?php

namespace App\Support;

/**
 * CIMA-040: authoritative server-side list of theme preset identifiers.
 *
 * Keep in sync with `resources/js/lib/themes.ts` (the CSS token blocks in
 * `resources/css/app.css` carry the matching palette definitions). Used for
 * validating hospital branding inputs (ADR-005).
 */
final class ThemePresets
{
    public const IDS = [
        'clinical-indigo',
        'emerald-care',
        'ocean-blue',
        'royal-violet',
        'teal-medical',
        'warm-amber',
        'rose-professional',
        'slate-night',
    ];

    public const DEFAULT = 'clinical-indigo';
}
