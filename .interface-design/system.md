# Interface system

## Direction

Clinical operations for hospital staff: calm, legible, and focused on patient flow and bed availability. The dashboard's focal point is the current inpatient census, supported by operational metrics rather than decorative charts. Use the existing clinical-indigo theme and semantic color tokens so the interface remains consistent with user-selected themes and dark mode.

## Product vocabulary and visual signature

- Domain: admissions, inpatients, wards, bed capacity, availability, cleaning, out of service.
- Color world: clinical indigo for focus; semantic success for available beds; warning for beds being cleaned; destructive for out-of-service beds; muted surfaces for supporting information.
- Signature: the ward occupancy strip combines occupied, available, cleaning, and out-of-service counts into one labeled view of operational bed state.
- Avoid generic equal-weight KPI tiles, decorative gradients, and charts that imply trends unsupported by the underlying data.

## Foundations

- Typography: Instrument Sans from the app theme. Use weight and muted text color before increasing size; use tabular numerals for metrics.
- Hierarchy: 14px body, 12px supporting labels, 16–18px section headings, 30–36px page heading, and 48–60px focal metric. Keep large headings tightly tracked.
- Spacing: 4px base unit; use Tailwind spacing utilities on that grid. Dashboard content uses 20px mobile, 28px small-screen, and 32px desktop padding, with 32px between major sections.
- Surfaces: semantic background/card tokens, subtle borders, and restrained card shadows. Use one elevation strategy; do not introduce unrelated hard-coded color surfaces.
- Corners: 8px controls, 12px compact icon tiles, and 16px dashboard cards and hero.
- Interaction: use native links and buttons or existing UI primitives; preserve visible focus styles and minimum 40px targets. Respect reduced-motion preferences.

## Dashboard patterns

- Page frame: centered content up to 1440px; responsive layout with the inpatient/occupancy hero beside a compact metric stack.
- Hero metric: semantic primary surface, 24px mobile / 32px desktop padding, 48–60px tabular value, and a labeled occupancy progress bar when bed data is available.
- Summary metric: semantic card surface, 20px padding, minimum 128px height, a clear label/value/supporting note, and a quiet secondary icon.
- Ward occupancy: list wards with installed-bed context, occupied/available/offline counts, and an accessible segmented status bar. Distinguish cleaning from out-of-service in the labels.
- Recent admissions: show patient identifiers only when the viewer has both patient-view and encounter-management permissions. Keep the list compact and provide an explicit empty state.
- States: include useful empty states for unconfigured wards and no admissions; omit metrics the viewer cannot access instead of implying they are zero.

## Public homepage patterns

- Audience: visitors locating a ward display and staff entering the protected operations area.
- Header: hospital logo/name at left; ward directory and staff sign-in at right. Keep the public directory as the primary call to action.
- Hero: large, tightly tracked headline and short plain-language explanation beside a compact public-ward preview. Use the actual ward list and actual refresh interval; never invent operational data.
- Ward directory: responsive linked cards with ward name/code and an explicit open-display action. A small three-ward preview may link to the same full directory below.
- Public status: describe the displays as automatically refreshed, not as a guaranteed live connection. Show the real configured ward count and a clear empty state when none exist.
- Identity: use shared hospital branding and semantic theme tokens; avoid starter-template content, stock imagery, or decorative hospital claims not backed by the application.
