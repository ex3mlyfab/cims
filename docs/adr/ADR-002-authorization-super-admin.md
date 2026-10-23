# ADR-002: Authorization Strategy

**Status:** Accepted (verify Spatie ULID compatibility before Phase 2 install)
Fortify provides authentication; Spatie laravel-permission provides RBAC. Roles seeded idempotently (firstOrCreate by name/permission strings). Super Admin granted via `Gate::before(fn($user) => $user->hasRole('super admin') ? true : null)` — returns null for everyone else so policies still apply; tested to not bypass unrelated boundaries. Every protected route/action checked server-side (middleware + policies). Nav hiding is UX only. The seeded Super Admin account: credentials from env, never hardcoded; last-active Super Admin cannot be deactivated without an explicit recovery procedure.
