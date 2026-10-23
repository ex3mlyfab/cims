# ADR-004: Audit Trail Design

**Status:** Accepted
`audit_logs` (ULID) records actor, action, record type/id, before/after summaries, auth context (IP/agent), timestamps. Written by an `AuditLogger` service invoked within the surrounding transaction; append-only — app workflows never delete rows, no cascading deletes into it. Forbidden content: passwords/tokens/secrets, full clinical notes, bulk PHI dumps (position changes, not narrative content).
