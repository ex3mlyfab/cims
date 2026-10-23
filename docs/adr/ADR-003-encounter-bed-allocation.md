# ADR-003: Encounter & Bed Allocation Model

**Status:** Accepted
Patients ≠ encounters: a patient has many encounters. Encounters carry arrival/type/team, not a bed. `bed_allocations` is a historical ledger: one row per occupancy period with `started_at`, nullable `ended_at`, `started_by`, `ended_by`, bed + encounter refs. Active allocation = `ended_at IS NULL`; DB-adjacent uniqueness guard plus `SELECT ... FOR UPDATE` transactions prevent double allocation. Transfer closes the old allocation and opens a new one atomically (allocation-time history is written to `bed_events`). Discharge ends encounter and allocation in one transaction. Bed operational state (`available|cleaning|out_of_service`) never contradicts occupancy state.
