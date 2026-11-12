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
