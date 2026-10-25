import { createInertiaApp } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { initializeThemePreset } from '@/hooks/use-theme-preset';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

function ConnectionStatus() {
    const [isOnline, setIsOnline] = useState<boolean>(() => navigator.onLine);

    useEffect(() => {
        const handleOnline = (): void => {
            setIsOnline(true);
            toast.success('Connection restored.');
        };

        const handleOffline = (): void => {
            setIsOnline(false);
            toast.warning(
                'You are offline. Clinical actions are unavailable until the connection is restored.',
            );
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    if (isOnline) {
        return null;
    }

    return (
        <div
            aria-live="polite"
            role="status"
            className="fixed inset-x-0 top-0 z-50 border-b border-amber-200 bg-amber-500 px-4 py-2 text-center text-sm font-medium text-amber-50 shadow-sm"
        >
            You are offline. Changes will not be saved until the connection
            returns.
        </div>
    );
}

const registerServiceWorker = (): void => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                const promptForUpdate = (): void => {
                    toast('Update available', {
                        description:
                            'Reload to apply the latest version of the app.',
                        action: {
                            label: 'Reload',
                            onClick: () => {
                                if (registration.waiting) {
                                    registration.waiting.postMessage({
                                        type: 'SKIP_WAITING',
                                    });
                                }

                                window.location.reload();
                            },
                        },
                    });
                };

                if (registration.waiting) {
                    promptForUpdate();
                    return;
                }

                registration.addEventListener('updatefound', () => {
                    const installingWorker = registration.installing;

                    if (!installingWorker) {
                        return;
                    }

                    installingWorker.addEventListener('statechange', () => {
                        if (
                            installingWorker.state === 'installed' &&
                            navigator.serviceWorker.controller
                        ) {
                            promptForUpdate();
                        }
                    });
                });
            })
            .catch(() => undefined);
    });
};

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome' || name.startsWith('display/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                <ConnectionStatus />
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
initializeThemePreset();
registerServiceWorker();
