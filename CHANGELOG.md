# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- CI: branch coverage is measured (Xdebug path coverage, `make coverage-branches`, Cobertura report) and enforced at
  96 % minimum (measured 96.1 %), above the 80 % of the OpenSSF gold criterion. The job `coverage-branches` runs on
  `main`, nightly (the CI workflow now has a nightly schedule) and on demand.
- Security assurance case (threat model, trust boundaries, secure design principles, countered weaknesses) and the
  November 2026 security review, in `docs/security/`, linked from `SECURITY.md` and the README.
- Project governance: `CODE_OF_CONDUCT.md` (Contributor Covenant 2.1), `GOVERNANCE.md` (decision model, roles and
  responsibilities), `ROADMAP.md` (next twelve months), and the code review requirements and first-contribution
  tasks in `CONTRIBUTING.md`.
- Developer Certificate of Origin: every commit is signed off (`git commit -s`), checked on each pull request by the
  CI job `dco` (`bin/check-dco`).
- `CONTRIBUTING.md` states the mandatory test policy: every feature and bug fix comes with automated tests.
- Unit tests of `ConfigurationRules` and `GatewayOptionsFactory`.

### Changed

- Every source file now carries a copyright and `SPDX-License-Identifier: MIT` header, enforced by PHP CS Fixer
  (`header_comment`) for PHP and by `make guard` (`bin/check-spdx`) for the other source files.
- CI: the SonarQube Cloud analysis also requires the repository variable `SONARCLOUD=enabled`, set once the
  project is imported on SonarQube Cloud.

## [0.1.2] - 2026-11-15

### Added

- Signed releases (workflow `release.yml`): each GitHub release carries the distribution archive, its `SHA256SUMS`
  and a SLSA build provenance attestation (`*.intoto.jsonl`, Sigstore); see *Verifying a release* in SECURITY.md.
- CodeQL analysis of the GitHub Actions workflows (workflow `codeql.yml`) on every pull request and push to `main`.

### Changed

- `composer.json` `homepage` now points to the project page, shown by Packagist:
  https://the-bearded-bear.com/open-source/urssaf-attestations-verification-code-securite. The README links to it
  and to the overview of the URSSAF packages.

## [0.1.1] - 2026-11-15

### Added

- Benchmarks (`tests/Benchmark`, `make bench`, PHPBench isolated in `tools/phpbench`): validation of a full batch of
  security codes, encoding of a full verification request, decoding of the official responses; the CI job `benchmark`
  compares each pull request with its base commit and fails above a 20 % regression.
- Published quality proofs: Codecov (coverage), Stryker Dashboard (mutation report of `main`), SonarQube Cloud (job
  `sonarcloud`) and OpenSSF Scorecard (workflow `scorecard.yml`), with their badges in the README.

### Changed

- CI: every GitHub Action is pinned to a commit SHA, kept up to date by Dependabot (`.github/dependabot.yml`, also
  for Composer).
- Dockerfile linted by hadolint (`make hadolint`, part of `make ci`, CI job `hadolint`); the development image runs as
  a non-root user by default.
- CI: GitHub Actions updated to their latest major versions (Dependabot).
- SonarQube Cloud coverage measured on the package code only (`sonar.coverage.exclusions`); live proof badges in
  the README (Codecov, Stryker mutation score, SonarQube Cloud, OpenSSF Scorecard).

- README: maintainer (The Bearded Bear), the other URSSAF packages and professional support; composer
  `authors` now points to https://the-bearded-bear.com.

- The Symfony Flex recipe is published on the public `symfony/recipes-contrib` repository (ADR 0009, superseding
  ADR 0008): enable contrib recipes with `composer config extra.symfony.allow-contrib true`.

### Removed

- The private Flex endpoint generator `bin/build-flex-endpoint`, the CI job `flex-endpoint` and the `symfony/process`
  development dependency.

## [0.1.0] - 2026-11-15

### Added

- Project skeleton: Docker tooling, quality gates (`make ci`) and GitHub Actions CI, identical to the other URSSAF
  packages.
- Specification: official OpenAPI contracts (`docs/openapi/`), official documentation (links in `docs/api-reference.md`, local copy in the git-ignored `docs/reference/`), technical
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
- Symfony Flex recipe (`recipe/`): registers the bundle, copies a commented configuration (simulated gateway in the test environment) and adds the `URSSAF_*` variables to `.env`; `bin/build-flex-endpoint` builds a private Flex endpoint, published by the CI job `flex-endpoint` (ADR 0008).

### Changed

- The package is open source under the MIT license (previously proprietary). Added `CONTRIBUTING.md` and
  `SECURITY.md`, and a notice in the README stating that the package is unofficial and not affiliated with URSSAF.
- The official URSSAF documents are no longer versioned: `docs/reference/` is a local, git-ignored folder.

[Unreleased]: https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/compare/v0.1.2...HEAD
[0.1.2]: https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/releases/tag/v0.1.0
