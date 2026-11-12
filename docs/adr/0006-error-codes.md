# ADR 0006 — Error codes and error payloads

- Status: accepted
- Spec: api-reference §1.3, §3.2, §5

## Context

Request-level errors come as a list of `{code, message, description}` (HTTP 400) or as a single object (HTTP 500
`ERR_TECHNIQUE`). Per-code faults come inside an HTTP 200. Security errors (400, 401, 403, 512) have no documented
body. `FUNC013` currently means both "service call failure" and "more than 100 codes"; the latter is announced to
become `FUNC016`.

## Decision

- `Infrastructure\Urssaf\Exception\ApiErrorCode` lists the request-level codes: `FUNC013`, `FUNC015`, `FUNC016`
  (announced), `ERR_TECHNIQUE`. `fromCode()` returns null for an unknown code, which is kept raw in `ApiError`.
- Per-code faults `FUNC201`, `FUNC202`, `FUNC203` belong to the domain (`Domain\Certificate\FaultCode`, ADR 0002).
- `ErrorPayloadParser` (reference) accepts a list, a single object or an `errors` envelope; an unreadable body yields
  no errors. HTTP 400 → `UrssafRequestRejected` with the parsed `ApiErrors`, possibly empty (undocumented security
  error body).
- HTTP status mapping follows ADR 0000 #5.

## Consequences

- Because the gateway never sends an empty or oversized list (ADR 0003), `FUNC013`/`FUNC015`/`FUNC016` on the POST can
  only mean an upstream failure or a contract change; they surface as `UrssafRequestRejected` with their codes.
