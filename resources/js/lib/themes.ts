export type ThemePresetId =
    | 'clinical-indigo'
    | 'emerald-care'
    | 'ocean-blue'
    | 'royal-violet'
    | 'teal-medical'
    | 'warm-amber'
    | 'rose-professional'
    | 'slate-night';

export type ThemePreset = {
    id: ThemePresetId;
    name: string;
    /** Approximate primary/accent/background hexes for selector swatches. */
    swatches: { primary: string; accent: string; background: string; dark: string };
};

export const DEFAULT_THEME_PRESET: ThemePresetId = 'clinical-indigo';

export const THEME_PRESETS: ThemePreset[] = [
    {
        id: 'clinical-indigo',
        name: 'Clinical Indigo',
        swatches: { primary: '#5b4fd6', accent: '#4285f4', background: '#f7f6fc', dark: '#1b1735' },
    },
    {
        id: 'emerald-care',
        name: 'Emerald Care',
        swatches: { primary: '#17976b', accent: '#22b8a6', background: '#f4faf7', dark: '#14201b' },
    },
    {
        id: 'ocean-blue',
        name: 'Ocean Blue',
        swatches: { primary: '#3b70e0', accent: '#5cc0e5', background: '#f5f8fc', dark: '#181d2b' },
    },
    {
        id: 'royal-violet',
        name: 'Royal Violet',
        swatches: { primary: '#6d3fc4', accent: '#38bfc6', background: '#f8f5fc', dark: '#1c142b' },
    },
    {
        id: 'teal-medical',
        name: 'Teal Medical',
        swatches: { primary: '#0d8b97', accent: '#6d6eed', background: '#f3fafb', dark: '#142023' },
    },
    {
        id: 'warm-amber',
        name: 'Warm Amber',
        swatches: { primary: '#d08a1f', accent: '#cf5f36', background: '#fbf8f2', dark: '#251d12' },
    },
    {
        id: 'rose-professional',
        name: 'Rose Professional',
        swatches: { primary: '#d94863', accent: '#d08456', background: '#fbf6f7', dark: '#231518' },
    },
    {
        id: 'slate-night',
        name: 'Slate Night',
        swatches: { primary: '#525f70', accent: '#3e93b8', background: '#f6f7fa', dark: '#20242c' },
    },
];

export const isThemePresetId = (value: string | null): value is ThemePresetId =>
    value !== null && THEME_PRESETS.some((preset) => preset.id === value);
