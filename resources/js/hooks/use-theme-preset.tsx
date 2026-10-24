import { useSyncExternalStore } from 'react';
import { DEFAULT_THEME_PRESET, isThemePresetId, type ThemePresetId } from '@/lib/themes';

export type UseThemePresetReturn = {
    readonly preset: ThemePresetId;
    readonly updatePreset: (preset: ThemePresetId, options?: { persist?: boolean }) => void;
    readonly resetPreset: () => void;
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

/** Initialize the preset on boot (localStorage wins; the cookie mirrors it). */
export function initializeThemePreset(): void {
    if (typeof window === 'undefined') return;

    currentPreset = getStoredPreset();
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

    const resetPreset = (): void => {
        currentPreset = DEFAULT_THEME_PRESET;
        applyThemePreset(DEFAULT_THEME_PRESET);
        localStorage.removeItem(PRESET_STORAGE_KEY);
        clearCookie();
        notify();
    };

    return { preset, updatePreset, resetPreset } as const;
}
