# Implementation roadmap

> Status: items 1–5 delivered; item 6 (contract tests) delivered, the open points of api-reference §5 remain until the tests run against the sandbox.

Specification: [api-reference.md](api-reference.md). Architecture, conventions and quality gates are the same as in
`thebeardedbear/urssaf-tiers-prestations` (hexagonal layers enforced by Deptrac, PSR-only runtime, Symfony and
Doctrine bridges, 100 % coverage and MSI).

## Already in place (skeleton)

- `SpecVersion::CURRENT` (`1.2.1`).
- `Infrastructure\Urssaf\Environment` (Sandbox / Production base URIs) and `Infrastructure\Urssaf\UrssafEndpoints`
  (token path, scope `attn.codes_securite`, base path `/attn/verification/v1`).

## Domain (`src/Domain`)

- `Certificate\SecurityCode` (value object: non-empty, trimmed; stricter format only once confirmed, see api-reference §5).
- `Certificate\CertificateNature` (enum, §3.1; unknown values preserved through a raw fallback).
- `Certificate\VerifiedCertificate` (nature, holder name, issuing body, issue date, nature-specific details).
- `Certificate\Details\*` (one value object per `infosSecurisationSpecifiques*` key: AMP, AVG, CSG, CFP, EPM, FRS,
  CAAE, ASAA) and `Establishment` (SIRET, address).
- `Certificate\RejectedCode` (security code + fault code `FUNC201|FUNC202|FUNC203|other` + fault name).
- `Certificate\VerificationResult` (sum type: `VerifiedCertificate|RejectedCode`).
- `Domain\Exception\*`.

## Application (`src/Application`)

- Port `Port\Urssaf\CertificateVerificationGateway` (`verify(SecurityCode)`, `verifyMany(list<SecurityCode>)`).
- Ports `CredentialsProvider`, `TokenStore` (same contracts as Tiers de prestations).
- Queries: `VerifyCertificate` / `VerifyCertificateHandler`, `VerifyCertificates` / `VerifyCertificatesHandler`
  (chunking by 100, order preserved).

## Infrastructure (`src/Infrastructure`)

- `Urssaf\Http\OAuth2TokenProvider`, `JsonApiClient`, `RetryAfter` (reuse the Tiers de prestations design).
- `Urssaf\UrssafHttpGateway` + `Urssaf\Wire\*` mappers (date `DD/MM/YYYY`, nature-specific keys, fault items).
- `Urssaf\Exception\ApiErrorCode` (§3.2), error payload parser (list or single object).
- `ApiConfiguration` (environment + overridable base URI, `domaine` option).

## Bridges

- Symfony bundle `UrssafAttestationsVerificationCodeSecuriteBundle` (alias `urssaf_attestations_verification_code_securite`):
  configuration (credentials, environment, base URI, `domaine`), HTTP client, token cache pool, console command
  `urssaf:certificate:verify <code>...`.
- Doctrine bridge: not needed at first (stateless API). Optional verification log later.

## Runtime dependencies to add with the code

`ext-json`, `ext-mbstring`, `psr/http-client`, `psr/http-factory`, `psr/http-message`, `psr/clock`, `psr/log`,
`psr/simple-cache` (or `psr/cache`), plus the matching `suggest` entries (Symfony bridge).

## Tests

- Unit: value objects, mappers against `tests/Fixtures/wire/*.json`, handlers with fake gateway.
- Functional: Symfony bundle wiring.
- Contract (`make test-contract`, manual CI job): sandbox credentials `URSSAF_SANDBOX_CLIENT_ID/SECRET`, optional
  `URSSAF_SANDBOX_BASE_URI`; a known valid code and a known unknown code (`URSSAF_SANDBOX_VALID_SECURITY_CODE` / `URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE`, the valid one as a repository secret, the unknown one as a variable). First
  goal: settle the `domaine` open point.

## Order of work

1. Domain value objects and result sum type.
2. Wire mappers + fixtures.
3. HTTP gateway (OAuth2, errors, 429 handling).
4. Application queries.
5. Symfony bridge.
6. Contract tests, then remove the open points from `api-reference.md`.
