# Skill: cima-database

Read this before any migration/model work.

## Non-negotiables
1. Every application-owned table: ULID primary key (`$table->ulid('id')->primary()`), model uses `HasUlids`.
2. FKs to ULID PKs use `foreignUlid()->constrained(<table>)`, **cancel** on parent delete for clinical/audit tables: `->restrictOnDelete()`.
3. Never auto-increment integers for application-owned tables. Route binding by ULID (default).
4. Third-party schema (Spatie Permission, Fortify/passkeys): only modify after verifying the package supports ULIDs; document adaptations in ADR-002/plan. Do not blindly rewrite vendor migrations.
5. Unique constraints on business identifiers (ward code, bed number per ward, patient number). Check constraints where MySQL supports them.
6. Transactions for allocation/transfer/discharge; immutable `*_events` and `audit_logs` history — append-only, no cascade deletes.
7. Factories/seeders generate ULIDs naturally; keep seeders idempotent (`firstOrCreate`).
8. Statuses stored as string enums (PHP backed enums) with DB string columns — human-readable, validated in Form Requests. Never collapse treatment status, encounter status, occupancy, and disposition into one field.
9. Migrations only — no undocumented schema changes. Tables get `timestamps()`; historical tables also `started_at/ended_at` where relevant.
