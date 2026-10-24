# Skill: cima-architecture

Read this before implementing any CIMA module.

## Rules

1. **Thin controllers** — controllers dispatch to Actions/Services (`app/Actions/*`, `app/Services/*`) or return Inertia responses with view-data only. No business rules in controllers or routes.
2. **Form Requests** for validation; message customization there, not inline.
3. **Policies** for every model with protected operations; combine with Spatie permissions — permissions gate "who may act on this widget", policies gate "is this record theirs".
4. **Transactions** around every multi-record clinical operation (allocate, transfer, discharge, encounter close). Lock rows that decide state (`SELECT FOR UPDATE`).
5. **Inertia conventions:** pages in `resources/js/pages/<area>/`, props explicitly typed in `resources/js/types`, server-side pagination/filtering, never send more PHI than the page needs.
6. **Errors:** domain-expected failures throw typed exceptions mapped in `bootstrap/app.php` or caught in the service; unexpected failures log with context and show a generic message. Never echo internal IDs in user-facing errors where unnecessary.
7. **No speculative abstraction.** Common UI is shadcn-based components, not bespoke wrappers per page. Duplication beats a wrong abstraction.
8. **Events/jobs** where timing matters (notifications, display updates) — keep the request fast.
9. Every service method asserts authorization is already handled (controllers/policies) and state preconditions with clear exceptions, e.g. `BedUnavailable`.
10. Testable: services take models/collections, not Request objects.
