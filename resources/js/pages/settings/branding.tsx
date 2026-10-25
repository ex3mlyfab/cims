import { Form, Head } from '@inertiajs/react';
import BrandingController from '@/actions/App/Http/Controllers/Settings/BrandingController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { edit } from '@/routes/branding';
import { THEME_PRESETS, type ThemePreset } from '@/lib/themes';
import type { HospitalBrandingForm } from '@/types/branding';

/**
 * CIMA-040: admin branding form (ADR-005) — name, logo asset, display text
 * and the hospital theme defaults. Asset uploads validate server-side
 * (PNG/JPG/WEBP/SVG, 512 KB max).
 */
export default function Branding({ branding }: { branding: HospitalBrandingForm }) {
    const presetDots = (p: ThemePreset) => (
        <span
            aria-hidden
            className="mr-2 flex shrink-0 overflow-hidden rounded-full border border-border"
            style={{ backgroundColor: p.swatches.background }}
        >
            <span className="h-3 w-3" style={{ backgroundColor: p.swatches.primary }} />
            <span className="h-3 w-3" style={{ backgroundColor: p.swatches.accent }} />
        </span>
    );

    const allowedPresets = branding.allowedPresets ?? THEME_PRESETS.map((p) => p.id);
    const currentLogo = branding.logoUrl;

    return (
        <>
            <Head title="Hospital branding" />

            <h1 className="sr-only">Hospital branding</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Hospital branding"
                    description="Set the hospital name, logo and theme defaults. These are hospital-wide defaults — personal appearance choices always win."
                />

                <Form
                    {...BrandingController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Hospital name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={branding.name ?? ''}
                                    name="name"
                                    required
                                    maxLength={60}
                                    placeholder="St. Example Hospital"
                                    data-test="branding-name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="display_text">
                                    Public display text
                                </Label>

                                <Input
                                    id="display_text"
                                    className="mt-1 block w-full"
                                    defaultValue={branding.displayText ?? ''}
                                    name="display_text"
                                    maxLength={120}
                                    placeholder="Shown on the waiting-room display"
                                    data-test="branding-display-text"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.display_text}
                                />
                            </div>

                            <Separator />

                            <div className="grid gap-2">
                                <Label htmlFor="logo">Hospital logo</Label>

                                {currentLogo && (
                                    <div className="flex items-center gap-3">
                                        <img
                                            src={currentLogo}
                                            alt="Current hospital logo"
                                            className="size-12 rounded-md border border-border bg-background object-contain p-1"
                                        />
                                        <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                            <input
                                                type="checkbox"
                                                name="remove_logo"
                                                value="1"
                                                className="size-4 accent-primary"
                                                data-test="branding-remove-logo"
                                            />
                                            Remove current logo
                                        </label>
                                    </div>
                                )}

                                <Input
                                    id="logo"
                                    type="file"
                                    name="logo"
                                    accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                    className="mt-1 cursor-pointer file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1 file:text-sm file:font-medium hover:file:bg-muted/80"
                                    data-test="branding-logo"
                                />

                                <p className="text-xs text-muted-foreground">
                                    PNG, JPG, WEBP or SVG, up to 512 KB.
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.logo}
                                />
                            </div>

                            <Separator />

                            <div className="max-w-sm grid gap-2">
                                <Label htmlFor="default_preset">
                                    Default colour theme
                                </Label>

                                <Select
                                    name="default_preset"
                                    defaultValue={
                                        branding.defaultPreset ??
                                        'clinical-indigo'
                                    }
                                    data-test="branding-default-preset"
                                >
                                    <SelectTrigger
                                        id="default_preset"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select the default theme" />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-80">
                                        <SelectGroup>
                                            {THEME_PRESETS.map((p) => (
                                                <SelectItem
                                                    key={p.id}
                                                    value={p.id}
                                                    textValue={p.name}
                                                >
                                                    <span className="flex items-center gap-3 py-1">
                                                        {presetDots(p)}
                                                        <span className="font-medium">
                                                            {p.name}
                                                        </span>
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>

                                <InputError
                                    className="mt-2"
                                    message={errors.default_preset}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label>Allowed colour themes</Label>

                                <p className="text-xs text-muted-foreground">
                                    Leave all unchecked to allow every palette.
                                    Unchecked palettes are hidden from the
                                    personal appearance selector.
                                </p>

                                <div className="mt-1 grid grid-cols-2 gap-2">
                                    {THEME_PRESETS.map((p) => (
                                        <label
                                            key={p.id}
                                            className="flex items-center gap-2 text-sm has-[:focus-visible]:rounded-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                        >
                                            <input
                                                type="checkbox"
                                                name="allowed_presets[]"
                                                value={p.id}
                                                defaultChecked={allowedPresets.includes(
                                                    p.id,
                                                )}
                                                className="size-4 accent-primary"
                                                data-preset={p.id}
                                            />
                                            {presetDots(p)}
                                            <span>{p.name}</span>
                                        </label>
                                    ))}
                                </div>

                                <InputError
                                    className="mt-2"
                                    message={errors['allowed_presets.0'] ?? errors.allowed_presets}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-branding"
                                >
                                    Save branding
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

Branding.layout = {
    breadcrumbs: [
        {
            title: 'Hospital branding',
            href: edit(),
        },
    ],
};
