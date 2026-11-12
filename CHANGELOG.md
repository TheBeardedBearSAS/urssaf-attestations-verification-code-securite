# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Project skeleton: Docker tooling, quality gates (`make ci`) and GitHub Actions CI, identical to the other URSSAF
  packages.
- Specification: official OpenAPI contracts (`docs/openapi/`), official documentation (`docs/reference/`), technical
  reference (`docs/api-reference.md`) and implementation roadmap (`docs/roadmap.md`).
- Architecture decision records (`docs/adr/`): reference architecture inherited from `urssaf-tiers-prestations`
  (0000), stateless SDK, verification result model, batching, quotas and retry, `domaine` parameter, error codes.
- Domain model: `SecurityCode`, certificate natures (§3.1), per-code fault codes (`FUNC201`–`FUNC203`), verification
  result sum type (`VerifiedCertificate` | `RejectedCode`) and one value object per nature-specific data key (AMP, AVG,
  CSG, CFP, EPM, FRS, CAAE, ASAA).
- Wire mappers for the verification response (`DD/MM/YYYY` issue date, nature-specific keys, per-code faults) and the
  multiple-verification request; request-level error payload parser (`FUNC013`, `FUNC015`, `FUNC016`, `ERR_TECHNIQUE`).
- HTTP gateway `UrssafHttpGateway` (built by `UrssafGatewayFactory`): OAuth2 client credentials with a pluggable PSR-6,
  PSR-16 or in-memory token store, GET single verification (configurable `domaine`, default `URSSAF`), POST multiple
  verification (1–100 codes), error mapping (`UrssafRequestRejected`, `UrssafAuthenticationFailed`, retryable
  `UrssafUnavailable` with `Retry-After` on 429/500/503).
- Application queries `VerifyCertificate` and `VerifyCertificates` (chunked by 100, input order preserved, an empty input makes no call).
- Framework-free entry point `Standalone\UrssafAttestationsVerificationBuilder` (`docs/standalone.md`).
- In-memory `Testing\SimulatedCertificateVerificationGateway` for host applications' tests (`docs/testing.md`).
- Symfony bundle `UrssafAttestationsVerificationCodeSecuriteBundle` (`urssaf_attestations_verification_code_securite`): configuration (environment, base URI, `domain`, credentials per tenant or provider, token cache pool, simulated gateway), public query handlers, commands `urssaf:certificate:verify` and `urssaf:check`, translations (en, fr) of the violation codes (`docs/symfony.md`).
- Opt-in contract tests against the URSSAF sandbox (`make test-contract`, manual CI job `contract`), first target: confirm whether `domaine` can be omitted.
