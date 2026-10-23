# ADR-006: PWA Offline Behaviour

**Status:** Accepted
Service worker caches: app shell, static assets (JS/CSS/manifest/icons) only. Explicitly never cached: patient data, encounters, allocations, audit data, auth responses. Offline shows a connection-loss banner; clinical mutations are never queued offline. Updates: SW upgrade invalidated-cache pattern with an "update available" prompt once safe. Future approved offline operations require a separate security-reviewed sync design.
