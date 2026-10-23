# ADR-001: ULID Primary Keys

**Status:** Accepted
Every application-owned table uses ULID primary keys via Eloquent `HasUlids` (`$table->ulid('id')->primary()`). Foreign keys use `foreignUlid()->constrained()` with explicit `restrictOnDelete()` for clinical/audit tables. Only vendor-managed tables (Spatie) follow that package's ULID-adapted schema. Public identifiers are never ULIDs. Migration tests must verify key generation and FK typing.
