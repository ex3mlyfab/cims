# ADR-005: Theme Persistence & Branding

**Status:** Accepted
Theme defined as Tailwind 4 CSS custom properties. Three layers with fixed precedence: (1) user session/manual choice, (2) authenticated user's saved preference, (3) hospital branding defaults. Appearance (palette + light/dark) stored per-user; hospital branding (name, logo, colors, allowed presets, display text) stored in a `hospital_branding` singleton and never silently overwrites user choices. Selector previews before applying; reset restores hospital default.
