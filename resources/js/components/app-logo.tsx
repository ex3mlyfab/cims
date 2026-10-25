import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

/**
 * CIMA-040: renders the hospital logo asset when branding defines one,
 * falling back to the built-in icon. Name comes from branding when saved.
 */
export default function AppLogo() {
    const { name, branding } = usePage().props;

    return (
        <>
            {branding?.logoUrl ? (
                <img
                    src={branding.logoUrl}
                    alt=""
                    className="size-8 shrink-0 rounded-md bg-sidebar-primary object-contain p-0.5"
                />
            ) : (
                <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                </div>
            )}
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {branding?.name ?? name}
                </span>
            </div>
        </>
    );
}
