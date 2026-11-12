# ADR 0003 — Batching of the multiple verification

- Status: accepted
- Spec: api-reference §2.2, §4.2

## Context

`POST /codes_securite/verifier` accepts at most 100 codes (`FUNC013` above, to become `FUNC016`) and refuses an empty
list (`FUNC015`). The response holds one item per requested code; the official example request contains a duplicate.

## Decision

- Gateway port `CertificateVerificationGateway::verifyMany(TenantId, SecurityCode ...$codes)` accepts 1 to
  `MAX_CODES_PER_CALL = 100` codes and throws `InvariantViolation` otherwise: the package never sends a request that
  URSSAF is documented to refuse.
- `verify()` uses the GET operation, `verifyMany()` the POST operation.
- `VerifyCertificatesHandler` chunks its input by 100, calls the gateway sequentially, and returns the concatenated
  results in input order. Duplicates are sent as given. An empty input returns `[]` without any call.
- When a response does not hold exactly one item per code sent, the gateway throws `UnexpectedUrssafResponse` (order
  can no longer be guaranteed). Results are taken in response order.

## Consequences

- A failure in a later chunk loses the results of the earlier ones for that call; callers that need partial results
  call the handler per chunk of 100.
