# ADR 0005 — `domaine` query parameter

- Status: accepted (to revisit after the sandbox contract test)
- Spec: api-reference §1.2, §2.1, §3.3, §4.4, §5

## Context

The PDF v1.2 documents `domaine` (`URSSAF` or `ISU`) as mandatory on the GET operation, while stating it is not needed
and will be removed. The Swagger v1.0.1 no longer declares it. The PDF does not mention it for the POST operation.

## Decision

- `Infrastructure\Urssaf\VerificationDomain` enum: `Urssaf = 'URSSAF'`, `Isu = 'ISU'`.
- `ApiConfiguration` holds a `?VerificationDomain`, default `VerificationDomain::Urssaf`; `null` omits the parameter.
  The Symfony bundle exposes it as `domain: URSSAF|ISU|null`.
- It is sent on the GET operation only. There is no per-call override.

## Consequences

- Default behaviour matches the most detailed official source (the PDF). The contract test checks both forms; once
  URSSAF confirms the parameter is ignored, the default can become `null` (minor version) and §5 is closed.
