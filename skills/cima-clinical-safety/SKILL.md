# Skill: cima-clinical-safety

Read before any clinical workflow code.

1. Never silently discard patient information; cancellations/overrides become recorded events.
2. Preserve encounter and bed-allocation history — close, never delete.
3. Every clinical event records timestamp + responsible user (server-side, from auth context, never client-supplied as authoritative).
4. A bed must have at most one active allocation; out-of-service beds are not allocatable. Enforce in service logic **and** test it under concurrency.
5. Current status vs historical status are distinct queries/fields; history is immutable.
6. Status definitions/labels come from hospital-approved config; proposed labels are flagged as such until sign-off.
7. Authorization checked for every clinical read/write.
8. Never present unverified/derived information as clinical fact; no autonomous diagnosis/treatment features.
9. App availability ≠ availability of a patient/bed — a stale UI must not masquerade as live clinical state.
