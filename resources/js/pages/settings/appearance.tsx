import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import ThemeSelector from '@/components/theme-selector';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Appearance settings" />

            <h1 className="sr-only">Appearance settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Appearance settings"
                    description="Update the appearance settings for your account"
                />
                <AppearanceTabs />
                <div className="space-y-4 rounded-lg border border-border bg-card p-4">
                    <div>
                        <h2 className="text-sm font-medium">Colour theme</h2>
                        <p className="text-sm text-muted-foreground">
                            Choose one of the eight CIMA palettes. Changes apply
                            instantly and are saved to your account storage.
                        </p>
                    </div>
                    <ThemeSelector />
                </div>
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Appearance settings',
            href: editAppearance(),
        },
    ],
};
