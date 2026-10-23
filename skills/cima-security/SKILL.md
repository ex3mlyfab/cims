# Skill: cima-security

Read before any route/controller/API work.

1. Authorization enforced server-side on every protected action (policies + Spatie middleware). Frontend hiding is cosmetic.
2. CSRF on all state-changing requests (Laravel default — don't disable it).
3. Validate input with Form Requests; escape output (React default escaping; never `dangerouslySetInnerHTML` with user content).
4. Rate-limit: login (Fortify), password updates (`throttle:6,1` exists), patient search, public display endpoint.
5. No IDOR: policies verify the record relationship; ULID keys are not a substitute.
6. Sessions: `SESSION_DRIVER=database`, secure/httponly cookies, `SESSION_ENCRYPT=true` as the target, HTTPS in production.
7. Audit sensitive changes (ADR-004). Never log secrets/PHI dumps.
8. Public display: dedicated projection only, no model dumps to the client.
9. No secrets or production credentials in code/seeders; use env vars.
10. Disabled users blocked at auth/license middleware, sessions invalidated on deactivation; audit history preserved.
11. Automated tests for each of the above in every phase.
