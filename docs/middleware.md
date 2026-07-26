# 🛡️ Middleware

Three middleware aliases are registered:

- `passage.complete:{passage}` requires the passage to be complete.
- `passage.step:{passage},{step}` requires a specific step to be satisfied.
- `passage.next:{passage}` redirects to the next step when it has a route or URL.

For JSON requests, incomplete passage middleware returns the configured status and a progress snapshot. For browser requests, it redirects to the step route/URL or the configured fallback route.

Passage middleware is a workflow gate, not an authorization system. Pair it with `auth`, policies, gates, and tenant checks.
