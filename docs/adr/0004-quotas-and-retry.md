# ADR 0004 — Quotas and retry

- Status: accepted
- Spec: api-reference §1.1

## Context

The reference package ships a client-side sliding-window rate limiter configured from the per-method quotas published
by URSSAF. This API documents two quota levels (global, per client) but publishes no values; exceeding them returns
HTTP 429.

## Decision

- No client-side rate limiter: there is no number to configure it with, and inventing one is forbidden.
- HTTP 429, 500, 503 and transport errors become `UrssafUnavailable` (`RetryableException`), with the `Retry-After`
  delay when sent in delta-seconds form (reference `RetryAfter`). The token endpoint follows the reference mapping.
- No automatic retry inside the package; the caller decides. Both operations are read-only, so a replay is always safe;
  `mayHaveReachedUrssaf()` is kept for consistency with the other URSSAF packages.

## Consequences

- A rate limiter can be reintroduced (same port as the reference) if URSSAF publishes the quotas.
