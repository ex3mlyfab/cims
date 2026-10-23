# CIMA — Project Status

**Last updated:** 2026-10-23

- **Current phase:** Phase 2 (Auth & RBAC) in progress — CIMA-020 complete.
- **Completed modules:** Planning docs/ADRs/skills; ULID conventions + MySQL switch (Phase 1); registration & self-service account deletion removed (CIMA-020).
- **In progress:** None.
- **Outstanding tasks:** CIMA-021 onward — see `IMPLEMENTATION_TASK_LOG.md`. Next: CIMA-021 (install Spatie Permission), CIMA-022 (Super Admin role + idempotent seeders).
- **Known defects:** Test DB remains sqlite `:memory:` (intentional fast CI path); MySQL is verified via local migration runs.
- **Test status:** Full suite passing — 44/44 tests; `npm run check`/`types:check` clean.
- **Blockers:** None. Stakeholder sign-off still recommended (non-blocking): public-display exact age vs age bands (R3), final treatment status labels (R4).
- **Next action:** CIMA-021 — install and configure Spatie laravel-permission for ULIDs.
