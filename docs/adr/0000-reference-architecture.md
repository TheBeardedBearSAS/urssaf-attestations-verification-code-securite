# ADR 0000 — Reference architecture inherited from `urssaf-tiers-prestations`

- Status: accepted
- Reference: `thebeardedbear/urssaf-tiers-prestations`, commit `6f6e290` (README "Architecture", `docs/api-reference.md` §4,
  `docs/workflow.md`, `docs/symfony.md`, `docs/doctrine.md`, `docs/testing.md`, `docs/standalone.md`, `deptrac.yaml`,
  `phpstan.neon.dist`, `mago.toml`, `rector.php`).

## Context

Every URSSAF package of the vendor shares one architecture, one set of conventions and one quality gate. The reference
package has no `docs/adr/` directory: its decisions live in the documents listed above and in the justified rule
exceptions of the tooling configuration. This record summarises them once and says, for each one, whether it applies to
this package, a **stateless, read-only** API (verify one or up to 100 certificate security codes, OAuth2 client
credentials).

Any new decision, or any departure from the reference, gets its own record `docs/adr/NNNN-<title>.md`
(Context / Decision / Consequences).

## Decision

| # | Inherited decision | Applies here? |
|---|---|---|
| 1 | **Hexagonal layers enforced by Deptrac.** Domain depends on nothing; Application → Domain; Infrastructure → Application, Domain, Psr; Bridges (Symfony, Doctrine) on the periphery; `Standalone` and `Testing` layers with the reference rules. | **Yes.** No `Contract` layer: there are no integration events (ADR 0001). |
| 2 | **PSR-only runtime.** `require` holds PHP extensions and PSR interfaces only (PSR-18/17/7 HTTP, PSR-20 clock, PSR-3 log, PSR-6/16 cache). Symfony and Doctrine stay in `require-dev` + `suggest`, with the reference `conflict` floors. Runtime dependencies are added with the code that uses them. | **Yes.** |
| 3 | **Value objects.** `final readonly`, private constructor, validating named constructor (`fromString()`), `equals()`, `value()`/`toString()`. English messages that never contain the rejected raw value; stable violation codes `<subject>.<reason>`. | **Yes** (`SecurityCode`, `TenantId`, `CalendarDate`, `Instant`, …). |
| 4 | **Sum types** as an interface with `final readonly` variants (reference: `DraftReading` → `CompleteDraft`/`IncompleteDraft`). Closed enums; unknown wire values are kept raw next to a nullable typed view (reference: `ApiErrorCode::fromCode(): ?self`). | **Yes.** `VerificationResult` = `VerifiedCertificate` \| `RejectedCode` (ADR 0002). |
| 5 | **Errors.** Marker `UrssafException` on every package exception; marker `RetryableException` for transient failures; abstract `DomainException` (`violationCode()`, `params()`, `toViolation()`) with `InvalidValue`, `InvariantViolation`, `RecordNotFound`. Gateway failures: `UrssafRequestRejected` (400), `UrssafAuthenticationFailed` (401 after one replay, 403, 512), `UrssafUnavailable` (429, 500, 503, transport; `Retry-After` delta-seconds; `mayHaveReachedUrssaf()`), `UnexpectedUrssafResponse` (anything else, malformed payloads). | **Yes.** `InvalidStateTransition` and `ValidationFailed` do not apply (no state machine, no multi-field draft). |
| 6 | **OAuth2 client credentials** via `OAuth2TokenProvider`: token cached in a `TokenStore` (PSR-6, PSR-16 or in-memory) under `<prefix>.sha256(environment\|baseUri\|clientId)`, renewed `token_safety_margin` (60 s) before expiry; on HTTP 401 the token is invalidated and the call replayed **once**. | **Yes.** Prefix `urssaf_avcs.`, scope `attn.codes_securite`. |
| 7 | **Secrets and logs.** `Credentials` is not serialisable, secrets are `#[SensitiveParameter]`, `__debugInfo()` masks them. Logs carry method, path, status, duration and request id only, never bodies or tokens. | **Yes**, extended by api-reference §4.6: `nir`, `denomination`, `nomArtisteAuteur`, `prenomArtisteAuteur` are masked in `__debugInfo()` and never logged. |
| 8 | **Multi-tenant.** `TenantId` is the first argument of every gateway method; `CredentialsProvider::forTenant(TenantId)` is the only source of secrets. | **Yes.** |
| 9 | **No internal retry/backoff.** The package classifies failures; retrying is the caller's job (cron, Messenger, …). | **Yes** (ADR 0004). |
| 10 | **Client-side rate limiter** (sliding window per tenant and operation, from published quotas). | **No.** Quotas are not published (api-reference §1.1). See ADR 0004. |
| 11 | **Workflow**: state machines, write idempotence, atomic claim, transactional outbox, domain and integration events, polling, stale reconciliation (`docs/workflow.md`, `docs/integration-events.md`). | **No.** Read-only, stateless API (ADR 0001). No `docs/workflow.md` nor `docs/integration-events.md`. |
| 12 | **Doctrine bridge**: XML mapping, custom DBAL types, dedicated entity manager, no shipped migrations (`docs/doctrine.md`). | **No** for now: nothing to persist (roadmap: "not needed at first"). The Deptrac layer and `src/Bridge/Doctrine/.gitkeep` stay; no `docs/doctrine.md`. |
| 13 | **Symfony bundle**: `AbstractBundle` (`configure()`, `loadExtension()`), `BundleConfiguration` + `ConfigurationRules` (container-time validation), `ServiceIds` constants, one registrar per concern, only application handlers public, `#[AsCommand]` commands guarded by `CommandReports::guard()`, `urssaf:check` with tagged `InstallationCheck` services, translations en/fr. | **Yes.** Messenger, Lock and Scheduler integrations do **not** apply (no background processing, no cron). |
| 14 | `recouv.application.id` header / `application_id` option. | **No.** Not part of this specification. |
| 15 | **Standalone builder** (framework-free entry point) and **Testing simulator** (public in-memory gateway for host applications' tests). | **Yes** (simplified to the two queries). |
| 16 | **Tests.** Unit tests with scripted fakes in `tests/Unit/**/Fake`; mapper tests on `tests/Fixtures/wire`; functional tests through a `TestKernel` + `Harness`; contract tests against the sandbox, opt-in (`SandboxGateway::fromEnvironment()` returns null when `URSSAF_SANDBOX_CLIENT_ID`/`SECRET` are missing or blank → `markTestSkipped`), `#[Group('contract')]`, outside coverage and Infection. `static::assert*()`, static data providers, random order, strict PHPUnit. | **Yes.** |
| 17 | **Quality gate** `make ci`: cs, rector, PHPStan max (strict, bleeding edge, 100 % type coverage), Mago, Deptrac, composer checks, guard, 100 % line/method/class coverage, 100 % MSI. Rule exceptions only when commented and justified (ergebnis `noExtends` allow-list, disabled DDD-hostile ergebnis rules, Mago `excessive-parameter-list`/`literal-named-argument`, Rector skips for static helpers and `static::assert*()`). | **Yes**, identical. The `noExtends` allow-list only names classes actually extended here. |
| 18 | **Language.** Field names, codes and wire values in French, verbatim from the specification; everything else in English. `SpecVersion::CURRENT` follows the official documentation file name. | **Yes.** |

## Consequences

- The package can be read by anyone who knows the reference: same layers, names and test layout.
- Decisions 10–12 and 14 are deliberately dropped. Their records (ADR 0001, 0004) say what would bring them back
  (published quotas, a verification log to persist).
- New records for this package: 0001 stateless SDK, 0002 verification result model, 0003 batching, 0004 quotas and
  retry, 0005 `domaine` parameter, 0006 error codes.
