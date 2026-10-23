# ADR-008: Real-time Updates for Public Display

**Status:** Accepted
Primary mechanism: Inertia partial-reload polling every 30s (no additional infra, works with Herd test domains). Duration expiration refreshes tick client-side. Stale indicator shown when a poll fails or is overdue; reconnection reloads. Future upgrade path: Laravel Reverb broadcasting — the endpoint shape is designed so swapping the transport does not change the projection.
