# CIMA — Implementation Task Log

Authoritative progress record. Rules: update on start/block/complete; mark COMPLETED only after acceptance criteria verified; never silently delete history.

**Statuses:** `NOT_STARTED`, `IN_PROGRESS`, `BLOCKED`, `COMPLETED`, `DEFERRED`

| Task ID | Phase | Task | Description | Dependencies | Status | Files | Tests | Outcome | Issues | Completed |
|---|---|---|---|---|---|---|---|---|---|---|
| CIMA-001 | 0 | Repository audit | Inspect Laravel/React/Fortify/Chisel versions, routes, migrations, env | None | COMPLETED | — | — | Laravel 13.38, Fortify + Chisel, React 19, Tailwind 4, SQLite default, no Spatie | MySQL switch pending (R2) | 2026-10-23 |
| CIMA-002 | 0 | Implementation plan | Write docs/IMPLEMENTATION_PLAN.md | CIMA-001 | COMPLETED | docs/IMPLEMENTATION_PLAN.md | — | Doc created; risks R1–R6 recorded | — | 2026-10-23 |
| CIMA-003 | 0 | Task log | Write this file | CIMA-002 | COMPLETED | docs/IMPLEMENTATION_TASK_LOG.md | — | Created | — | 2026-10-23 |
| CIMA-004 | 0 | Project status | Write docs/PROJECT_STATUS.md | CIMA-002 | COMPLETED | docs/PROJECT_STATUS.md | — | Created | — | 2026-10-23 |
| CIMA-005 | 0 | ADRs | Document ULIDs, RBAC, allocation, audit, theme, PWA, display, real-time | CIMA-002 | COMPLETED | docs/adr/*.md | — | ADR-001..008 created | — | 2026-10-23 |
| CIMA-006 | 0 | Skill files | Create skills/*/SKILL.md (7 skills incl. security, safety, UI, PWA) | CIMA-002 | COMPLETED | skills/** | — | Created | — | 2026-10-23 |
| CIMA-007 | 0 | Consistency check | Validate docs/skills cross-references and task breakdown | CIMA-005, CIMA-006 | COMPLETED | — | — | Verified | Proceed to Phase 1 | 2026-10-23 |
| CIMA-010 | 1 | ULID base convention | `HasUlids` trait/pattern, foreign-key helpers, factories convention | CIMA-007 | NOT_STARTED | | | | | |
| CIMA-011 | 1 | MySQL migration | Switch local + test DB to MySQL 8, verify connection | CIMA-010 | NOT_STARTED | | | | | |
| CIMA-012 | 1 | Convert auth-framing tables | ULID users/sessions/passkeys migrations without breaking Fortify/Chisel | CIMA-011, R1 | NOT_STARTED | | | | | |
| CIMA-013 | 1 | ULID feature tests | Key generation, FK compatibility, migrations rollback | CIMA-012 | NOT_STARTED | | | | | |
| CIMA-020 | 2 | Remove registration & self-deletion | Fortify registration closure, DELETE profile route/controller/UI removal | CIMA-013 | NOT_STARTED | | | | Preserve chisel markers |
| CIMA-021 | 2 | Install Spatie Permission | Composer install, ULID-compatible publish/migrations | CIMA-012 | NOT_STARTED | | | | |
| CIMA-022 | 2 | Super Admin + seeders | `super admin` role, IdempotentSeeder, Gate::before with tests | CIMA-021 | NOT_STARTED | | | | Protect last Super Admin |
| CIMA-023 | 2 | Roles/permissions seed | Initial role-permission matrix | CIMA-022 | NOT_STARTED | | | | |
| CIMA-024 | 2 | Authorization tests | Route/permission boundary tests, disabled account tests | CIMA-023 | NOT_STARTED | | | | |
| CIMA-030 | 3 | Theme token system | CSS variables, 8 presets, light/dark, solid backgrounds | CIMA-007 | NOT_STARTED | | | | |
| CIMA-031 | 3 | Theme selector UI | Preview, apply, reset, persistence (user + branding precedence) | CIMA-030 | NOT_STARTED | | | | |
| CIMA-032 | 3 | a11y + responsive verification | Contrast, keyboard, breakpoints | CIMA-031 | NOT_STARTED | | | | |
| CIMA-040 | 4 | Admin settings shell + branding | Nav groups, branding form, asset validation | CIMA-023, CIMA-031 | NOT_STARTED | | | | |
| CIMA-041 | 4 | Roles & permissions UI | List/create/edit, assign perms, protect super admin | CIMA-023 | NOT_STARTED | | | | |
| CIMA-042 | 4 | User management | CRUD, roles, deactivate, resets, audit | CIMA-041 | NOT_STARTED | | | | |
| CIMA-043 | 4 | Management team module | ULID personnel records distinct from users | CIMA-012 | NOT_STARTED | | | | |
| CIMA-044 | 4 | Ward management | CRUD, capacity, unsafe-delete prevention | CIMA-043 | NOT_STARTED | | | | |
| CIMA-045 | 4 | Bed management | CRUD, state machine, occupancy consistency | CIMA-044 | NOT_STARTED | | | | |
| CIMA-046 | 4 | Bed history | Append-only `bed_events` with actor/timestamps | CIMA-045 | NOT_STARTED | | | | |
| CIMA-050 | 5 | Patient registration | Patient number generator, demographics, duplicate detection | CIMA-012 | NOT_STARTED | | | | |
| CIMA-051 | 5 | Encounters | Encounter model, arrival, type, team | CIMA-050 | NOT_STARTED | | | | |
| CIMA-052 | 5 | Treatment statuses | Configurable workflow statuses + history events | CIMA-051 | NOT_STARTED | | | | |
| CIMA-060 | 6 | Allocation service | Transactional allocate with locking + unique guard | CIMA-046, CIMA-052 | NOT_STARTED | | | | |
| CIMA-061 | 6 | Transfer workflow | Close old allocation, create new, history | CIMA-060 | NOT_STARTED | | | | |
| CIMA-062 | 6 | Discharge/release | End allocation, bed state transitions | CIMA-060 | NOT_STARTED | | | | |
| CIMA-063 | 6 | Concurrency tests | Double-allocation race tests | CIMA-060 | NOT_STARTED | | | | |
| CIMA-070 | 7 | Public display endpoint | Privacy projection, masked ID, initials, duration semantics | CIMA-062 | NOT_STARTED | | | | |
| CIMA-071 | 7 | Display UI + updates | Large-screen table, polling, stale/empty states | CIMA-070 | NOT_STARTED | | | | |
| CIMA-072 | 7 | Privacy tests | No names/notes/ULIDs/history on public route | CIMA-071 | NOT_STARTED | | | | |
| CIMA-080 | 8 | PWA manifest + SW | Installability, static-only caching | CIMA-050 | NOT_STARTED | | | | |
| CIMA-081 | 8 | Offline/online feedback | Indicators, safe updates | CIMA-080 | NOT_STARTED | | | | |
| CIMA-090 | 9 | QA sweep | All test suites, production build, deployment docs | All | NOT_STARTED | | | | |

## Session Log

**2026-10-23 (Phase 0):** Completed repository audit and documentation. Next task: **CIMA-010** (ULID conventions for Phase 1). Blockers: none, but stakeholder sign-offs on R3/R4 recommended before Phase 5.
