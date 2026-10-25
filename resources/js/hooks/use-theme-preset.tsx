import { useSyncExternalStore } from 'react';
import { DEFAULT_THEME_PRESET, isThemePresetId, type ThemePresetId } from '@/lib/themes';

export type UseThemePresetReturn = {
    readonly preset: ThemePresetId;
    readonly updatePreset: (preset: ThemePresetId, options?: { persist?: boolean }) => void;
    readonly resetPreset: (target?: ThemePresetId) => void;
};

const listeners = new Set<() => void>();
let currentPreset: ThemePresetId = DEFAULT_THEME_PRESET;

const PRESET_STORAGE_KEY = 'cima-theme-preset';
export const PRESET_COOKIE_NAME = 'cima_theme_preset';

const setCookie = (value: string): void => {
    if (typeof document === 'undefined') return;
    document.cookie = `${PRESET_COOKIE_NAME}=${value};path=/;max-age=31536000;SameSite=Lax`;
};

const clearCookie = (): void => {
    if (typeof document === 'undefined') return;
    document.cookie = `${PRESET_COOKIE_NAME}=;path=/;max-age=0;SameSite=Lax`;
};

/**
 * Apply the preset by toggling the data attribute that the CSS preset blocks
 * in app.css react to. `clinical-indigo` is the default (no attribute).
 */
export function applyThemePreset(preset: ThemePresetId): void {
    if (typeof document === 'undefined') return;

    if (preset === DEFAULT_THEME_PRESET) {
        document.documentElement.removeAttribute('data-cima-preset');
    } else {
        document.documentElement.setAttribute('data-cima-preset', preset);
    }
}

/** Read the hospital default preset the blade shell already applied. */
const getPresetFromShell = (): ThemePresetId => {
    if (typeof document === 'undefined') return DEFAULT_THEME_PRESET;

    const applied = document.documentElement.getAttribute('data-cima-preset');

    return isThemePresetId(applied) ? applied : DEFAULT_THEME_PRESET;
};

const getStoredPreset = (): ThemePresetId => {
    if (typeof window === 'undefined') return DEFAULT_THEME_PRESET;

    const stored = localStorage.getItem(PRESET_STORAGE_KEY);

    return isThemePresetId(stored) ? stored : DEFAULT_THEME_PRESET;
};

const subscribe = (callback: () => void) => {
    listeners.add(callback);

    return () => listeners.delete(callback);
};

const notify = (): void => listeners.forEach((listener) => listener());

/**
 * Initialize the preset on boot.
 * ADR-005 precedence: stored user choice wins, then the hospital default
 * the blade shell already applied to <html>, then the built-in default.
 */
export function initializeThemePreset(): void {
    if (typeof window === 'undefined') return;

    const stored = localStorage.getItem(PRESET_STORAGE_KEY);

    currentPreset = isThemePresetId(stored)
        ? stored
        : getPresetFromShell();
    applyThemePreset(currentPreset);
    setCookie(currentPreset);
}

export function useThemePreset(): UseThemePresetReturn {
    const preset = useSyncExternalStore(
        subscribe,
        () => currentPreset,
        () => DEFAULT_THEME_PRESET,
    );

    const updatePreset = (next: ThemePresetId, options?: { persist?: boolean }): void => {
        applyThemePreset(next);

        if (options?.persist !== false) {
            currentPreset = next;
            localStorage.setItem(PRESET_STORAGE_KEY, next);
            setCookie(next);
        }

        notify();
    };

    /**
     * Reset to the hospital default preset (ADR-005). Persisted as an
     * explicit user choice so the cookie/localStorage stay consistent.
     */
    const resetPreset = (target?: ThemePresetId): void => {
        const hospitalDefault = target && isThemePresetId(target) ? target : DEFAULT_THEME_PRESET;

        currentPreset = hospitalDefault;
        applyThemePreset(hospitalDefault);

        if (hospitalDefault === DEFAULT_THEME_PRESET) {
            localStorage.removeItem(PRESET_STORAGE_KEY);
            clearCookie();
        } else {
            localStorage.setItem(PRESET_STORAGE_KEY, hospitalDefault);
            setCookie(hospitalDefault);
        }

        notify();
    };

    return { preset, updatePreset, resetPreset } as const;
}
