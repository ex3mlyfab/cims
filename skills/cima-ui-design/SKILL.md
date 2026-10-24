# Skill: cima-ui-design

Read before building or touching any page or component.

## Design direction

Colourful, polished clinical dashboard — **solid colour backgrounds as first-class values**; gradients only for branding/hero accents. Hierarchy: page background < card/panel < sidebar/topbar accents < overlays. No grey-on-grey, no visual clutter. Tables stay calm and readable inside colourful shells.

## Theme engine (mandatory)

- Tokens: `--background`, `--foreground`, `--card`, `--card-foreground`, `--sidebar`, `--sidebar-foreground`, `--primary`, `--primary-foreground`, `--secondary`, `--accent`, `--muted`, `--muted-foreground`, `--border`, `--input`, `--ring`, `--success`, `--warning`, `--destructive`, `--info` (+ `*-foreground` where paired).
- No hardcoded colours in page components — always tokens.
- Presets: Clinical Indigo, Emerald Care, Ocean Blue, Royal Violet, Teal Medical, Warm Amber, Rose Professional, Slate Night. Each has light + dark.
- Selector: preview before apply, light/dark toggle, primary/accent choice, solid background picker from approved palette, reset-to-default, instant application, persisted (ADR-005).
- Validate/clip combos that would fail 4.5:1 text contrast (3:1 large text).

## Components (shared, `resources/js/app/<area>` + existing `components/ui`)

App shell, sidebar/topbar, breadcrumbs, page header, summary cards, status badges (icon + text **never colour-only**), data tables (search/filter/pagination), auth forms, confirm dialogs, toasts (existing sonner), spinners, empty states, error states, theme selector, mobile nav.

## Accessibility

WCAG 2.2 AA, visible focus rings (`--ring`), full keyboard nav, touch targets ≥ 44px on tablets, `prefers-reduced-motion` respected.

## Forbidden

Per-page inconsistent visual language; colour-only status communication; hidden-until-hover critical actions; exposing sensitive data in decorative/off-screen elements.
