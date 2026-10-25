import { PaletteIcon, RotateCcwIcon } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useThemePreset } from '@/hooks/use-theme-preset';
import { THEME_PRESETS, DEFAULT_THEME_PRESET, type ThemePreset } from '@/lib/themes';

/*
 * CIMA-031: interactive theme selector.
 * CIMA-040: preset list is filtered to the hospital's allowed presets and
 * "Reset" restores the hospital default (ADR-005 precedence).
 */
export default function ThemeSelector({ className }: { className?: string }) {
    const { preset, updatePreset, resetPreset } = useThemePreset();
    const { branding } = usePage().props;

    const hospitalDefault = branding?.defaultPreset ?? DEFAULT_THEME_PRESET;
    const allowed = branding?.allowedPresets ?? THEME_PRESETS.map((p) => p.id);
    const availablePresets = THEME_PRESETS.filter((p) => allowed.includes(p.id));

    const swatchDots = (p: ThemePreset, dark?: boolean) => (
        <span
            aria-hidden
            className="mr-2 flex shrink-0 overflow-hidden rounded-full border border-border"
            style={{ backgroundColor: dark ? p.swatches.dark : p.swatches.background }}
        >
            <span className="h-3 w-3" style={{ backgroundColor: p.swatches.primary }} />
            <span className="h-3 w-3" style={{ backgroundColor: p.swatches.accent }} />
        </span>
    );

    const selected = THEME_PRESETS.find((p) => p.id === preset);

    return (
        <div className={className}>
            <div className="max-w-sm space-y-1.5">
                <Label htmlFor="theme-preset-select">Colour theme</Label>
                <Select value={preset} onValueChange={(value) => updatePreset(value as ThemePreset['id'])}>
                    <SelectTrigger
                        id="theme-preset-select"
                        className="w-full"
                        data-test="theme-selector-trigger"
                    >
                        {selected && swatchDots(selected)}
                        <SelectValue placeholder="Select a theme" />
                        <PaletteIcon className="h-4 w-4 text-muted-foreground" />
                    </SelectTrigger>
                    <SelectContent className="max-h-80">
                        <SelectGroup>
                            {availablePresets.map((p) => (
                                <SelectItem
                                    key={p.id}
                                    value={p.id}
                                    data-preset={p.id}
                                    className="py-2"
                                    textValue={p.name}
                                >
                                    <span className="flex items-center gap-3">
                                        {swatchDots(p)}
                                        <span className="font-medium">{p.name}</span>
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <p className="text-xs text-muted-foreground">
                    {selected?.name} applies instantly to every page and persists across
                    visits.
                </p>
            </div>

            <Button
                variant="ghost"
                size="sm"
                data-test="theme-reset"
                className="mt-4 text-muted-foreground"
                onClick={() => resetPreset(hospitalDefault)}
            >
                <RotateCcwIcon className="h-4 w-4" />
                {hospitalDefault === DEFAULT_THEME_PRESET
                    ? 'Reset to default'
                    : `Reset to ${THEME_PRESETS.find((p) => p.id === hospitalDefault)?.name ?? 'hospital default'}`}
            </Button>
        </div>
    );
}
