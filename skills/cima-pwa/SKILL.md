# Skill: cima-pwa

Read before PWA work (Phase 8).

1. Manifest: name, short name, theme colors from branding, icons 192/512 + maskable, `display: standalone`, start URL.
2. Service worker: precached app shell + static assets; `stale-while-revalidate` for assets; **never cache** responses from patient/encounter/audit/auth endpoints.
3. Offline: shell renders with an explicit offline indicator; clinical actions disabled rather than silently queued.
4. Updates: new SW version triggers a user-visible "update available" prompt; activation only in a safe barrier.
5. Connection state detection with accessible feedback (aria-live).
6. Test installability, offline shell, and absence of sensitive data in cache storage.
