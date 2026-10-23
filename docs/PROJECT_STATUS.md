# CIMA — Project Status

**Last updated:** 2026-10-23

- **Current phase:** Phase 1 (Foundation) complete → begin Phase 2 (Auth & RBAC).
- **Completed modules:** Planning docs/ADRs/skills; ULID conventions (users/sessions/passkeys); MySQL 8 local database (`cims`, root/localdb); ULID feature tests.
- **In progress:** None.
- **Outstanding tasks:** CIMA-020 onward — see `IMPLEMENTATION_TASK_LOG.md`. Next: CIMA-020 (remove registration & self-service account deletion).
- **Known defects:** Test DB remains sqlite `:memory:` (intentional fast CI path); MySQL is verified via local migration runs.
- **Test status:** Full suite passing — 44/44 tests, 143 assertions (`php artisan test`).
- **Blockers:** None. Stakeholder sign-off still recommended (non-blocking): public-display exact age vs age bands (R3), final treatment status labels (R4).
- **Next action:** CIMA-020 — close Fortify registration and self-service deletion while preserving Chisel/2FA/passkey features.
