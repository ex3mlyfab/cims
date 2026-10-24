# CIMA — Implementation Plan

Clinical Information Management Application for hospital emergency departments.

## 0. Repository Audit (Phase 0 findings)

| Area              | Finding                                                                                                                                                                                      |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Laravel           | v13.38.0 (PHP ^8.3) — matches required stack                                                                                                                                                 |
| Starter kit       | Laravel/React starter kit with **Fortify** (not plain Laravel Auth), Inertia 3, React 19 + TypeScript                                                                                        |
| Frontend tooling  | Vite 8 ("vite-plus"/vp), Tailwind CSS 4, shadcn/ui-style Radix components in `resources/js/components/ui`, React compiler enabled, Wayfinder for route typing                                |
| Auth              | Fortify: login, 2FA, passkeys, registration (self-service), profile update + **self-service account deletion** (`settings/profile` DELETE) — both must be removed                            |
| Database          | **Currently SQLite** (`.env` `DB_CONNECTION=sqlite`); MySQL required before Phase 1                                                                                                          |
| ULIDs             | Not present; all tables use auto-incrementing `id()`. `users`, `sessions`, `passkeys` migrations need ULID conversion or conversion of new tables + incremental plan for auth-framing tables |
| Spatie Permission | **Not installed** — must be added and its schema adapted for ULIDs                                                                                                                           |
| Testing           | PHPUnit 12 configured (`artisan test`); Pest not installed                                                                                                                                   |
| Appearance        | `HandleAppearance` middleware + `use-appearance` hook (light/dark) exists — good base for the theme engine                                                                                   |

## 1. Objectives & Scope

Manage ED patient flow: registration, clinical encounters, bed allocation/transfer, treatment progress, team coordination, auditable ward/bed management, a privacy-safe public waiting-room display, and an installable PWA.

**In scope:** ED/ward operations for one hospital site (multi-site deferred).
**Out of scope:** diagnosis/treatment decision support, pharmacy/lab systems integration, billing.

## 2. Architecture

- **Backend:** Laravel 13, thin controllers, Form Requests, dedicated Services/Actions (`app/Actions`/`app/Services`), Policies for authorization, DB transactions for allocation/transfer/discharge.
- **Frontend:** Inertia + React 19 + TS, shadcn/ui components, Tailwind 4 CSS-variable theme tokens, React Router-free (Inertia navigation), Wayfinder route helpers.
- **Database:** MySQL 8, ULID primary keys everywhere (`HasUlids`), explicit FK actions (restrict delete on history), no cascading deletes of clinical/audit data.
- **Authorization:** Spatie Laravel Permission + Fortify auth. Super Admin via `Gate::before` (strategy documented in ADR-002). Policies per model.
- **Audit:** `audit_logs` ULID table written by a dedicated `AuditLogger` service inside transactions.
- **Real-time (public display):** Inertia partial-reload polling first (30s, no infra requirement), upgradeable to Laravel Reverb broadcasting (ADR-008).

## 3. Database Design (major entities)

- `users` (ULID, `is_active`, last login) — converted
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (ULID-compatible per Spatie config)
- `wards`, `beds`, `bed_events` (append-only history), `bed_allocations` (with unique partial index preventing double allocation)
- `patients`, `encounters`, `encounter_status_events` (treatment progress history)
- `audit_logs`
- `hospital_branding` (singleton settings row)
- `management_team_members`
- Status model: **four distinct axes** — treatment workflow status, encounter status, bed occupancy, disposition (never one column).

### Bed state machine

`available → occupied → cleaning → available | out_of_service`. An allocation row must exist for an occupied bed; out-of-service beds cannot receive allocations (enforced in service + DB check where supported).

### Allocation integrity

`bed_allocations` has a MySQL functional unique index on `(bed_id, COALESCE(ended_at, sentinel))` equivalent enforced in the allocation service with `SELECT ... FOR UPDATE` inside a transaction; transferred allocations are closed, never deleted.

## 4. Auth & Authorization

- Keep login/2FA/passkeys/profile; **remove** registration, `CreateNewUser`, self-service account deletion (route, controller, UI).
- Admin-side user management: create, edit, assign roles, deactivate (preserving audit history).
- Roles (seeded idempotently): Super Admin, Hospital Administrator, ED Manager, Nurse, Doctor, Ward Manager, Bed Manager, Records Officer, Public Display Operator.
- Server-side checks on every route via middleware `permission:` and policies; nav links hidden without permission but never relied upon.

## 5. UI/UX & Theme

- App shell: responsive sidebar (grouped nav: Clinical Operations / Facility Management / Administration), top bar, breadcrumbs, permission-filtered nav.
- Theme engine: CSS custom properties (`--background`, `--primary`, …), 8 preset palettes (Clinical Indigo, Emerald Care, Ocean Blue, Royal Violet, Teal Medical, Warm Amber, Rose Professional, Slate Night), light/dark appearance, solid-color backgrounds, preview-then-apply selector, per-user persistence (cookies/localStorage + server preference for authenticated users), hospital branding defaults separate with defined precedence.
- WCAG 2.2 AA contrast; status badges always carry text labels + icons; keyboard nav; reduced motion.

## 6. Public Display

Dedicated unauthenticated route `/display`, server-side projection resource only: masked ID (first 3 chars masked), initials, bed number, sex, age, duration (since current allocation start, client-refreshed tick), treatment status. Polling with connection/stale indicators; disabled via a settings toggle. Privacy rules in ADR-007.

## 7. PWA

Manifest + modular service worker caching static assets only. No caching of clinical API/auth responses. Offline feedback banner; safe SW update prompt.

## 8. Security & Privacy

Form Request validation everywhere, CSRF, throttle on sensitive endpoints, IDOR protection via policy key checks (ULIDs help, but policies are the gate), audit logging, no secrets in repo, privacy-preserving public display.

## 9. Testing

PHPUnit (installed). Feature tests per phase: ULID integrity, authorization boundaries, allocation concurrency, public-display privacy, theme persistence. CI via `composer ci:check`.

## 10. Environment / Deployment

Migrate local DB from SQLite → MySQL 8 (Phase 1 gate). Production: `.env` secrets, HTTPS, `optimize`, scheduled tasks, DB backups + restore runbook (Phase 9).

## 11. Phases & Gates

| Phase | Content                                                                  | Gate                                          |
| ----- | ------------------------------------------------------------------------ | --------------------------------------------- |
| 0     | Audit + docs + skills (this)                                             | Docs complete & consistent                    |
| 1     | ULID conventions, MySQL, base trait, factories                           | ULID keys/FKs pass migration tests            |
| 2     | Auth hardening + Spatie RBAC + Super Admin                               | Registration/deletion removed; authz enforced |
| 3     | Branding + theme engine                                                  | Themes work app-wide, a11y checks pass        |
| 4     | Admin modules (users, roles, perms, mgmt team, wards, beds, bed history) | CRUD + permissions + history verified         |
| 5     | Patients + encounters + status workflow                                  | Lifecycle validated by tests                  |
| 6     | Allocation/transfer/discharge                                            | No invalid allocation states possible         |
| 7     | Public display                                                           | Privacy tests pass                            |
| 8     | PWA                                                                      | Installable, no sensitive caching             |
| 9     | QA + deployment                                                          | Release-critical tests pass                   |

## 12. Risks, Assumptions, Open Decisions

| #   | Item                                                       | Type       | Default adopted                                                                                |
| --- | ---------------------------------------------------------- | ---------- | ---------------------------------------------------------------------------------------------- |
| R1  | Spatie Permission ULID compatibility                       | Risk       | Verify package version & use package's ULID config; wrap ADR-002                               |
| R2  | MySQL not yet configured locally                           | Assumption | Switch `.env` in Phase 1; SQLite kept for CI fallback                                          |
| R3  | Age/sex on public display (privacy)                        | Decision   | Show age **bands** by default; exact age behind a branding toggle pending stakeholder approval |
| R4  | Status labels                                              | Assumption | Proposed workflow labels used until hospital sign-off                                          |
| R5  | Duplicate patient detection scope                          | Open       | Manual search-based reconciliation in Phase 5; automated matching deferred                     |
| R6  | Chisel scaffolding (Fortify/passkeys, `@chisel-*` markers) | Risk       | Preserve chisel markers/features while removing registration/deletion                          |
