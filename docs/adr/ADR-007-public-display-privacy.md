# ADR-007: Public Display Privacy

**Status:** Accepted
`/display` is unauthenticated and serves only a dedicated projection (JSON/Inertia partial reloads) with exactly: masked patient identifier (first **3** characters replaced with `*`, safe for short IDs), name initials, bed number, sex label, age (**band by default; exact age only behind an approved branding toggle**), duration = elapsed minutes since current allocation start (client-side ticking, no full refetch), treatment status label. Never: full names/IDs, ULIDs, contacts, notes, diagnoses, historical records. Only currently allocated patients. Expose an administrative enable/disable toggle for the display.
