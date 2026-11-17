# Security review, November 2026

| | |
|---|---|
| Date | 2026-11-17 |
| Reviewer | The project maintainer, assisted by an AI code assistant (Claude) |
| Scope | `thebeardedbear/urssaf-attestations-verification-code-securite`, commit `823dc4c7ddc07b35b16a4a5ec4772b26c45a59f4` (`main`, release v0.1.2 plus the later documentation changes): everything under `src/`, the Composer configuration, the GitHub workflows, composite action and Dependabot configuration |
| Reference | [Assurance case](assurance-case.md): security requirements SR1 to SR7, threat model and trust boundaries |
| Next review | At the latest in November 2027, or earlier after a change of a trust boundary |

## Method

1. **Design review against the security requirements and the trust boundaries** of the assurance case: for each
   boundary (host to package, package to URSSAF, package to cache, package to logs, pipeline to consumers) the data
   crossing it was identified and the control that protects it was located in the code.
2. **Manual code review** of the security-relevant code: credentials and token handling (`Application/Port/Credentials`,
   `AccessToken`, `Infrastructure/Urssaf/Http/OAuth2TokenProvider`, `Infrastructure/TokenStore/*`), the HTTP client and
   URL building (`JsonApiClient`, `ApiConfiguration`, `Environment`, `UrssafGatewayFactory`), the decoding and mapping
   of untrusted payloads (`Infrastructure/Urssaf/Wire/*`, `UrssafHttpGateway`), validation of security codes and tenant
   ids (`src/Domain/**`), logging and exception messages (every `logger->` call and exception factory), masking of
   personal data (`__debugInfo()` of the results), randomness (`Infrastructure/Id/*`), the Symfony bundle configuration,
   registrars and diagnostics.
3. **Targeted searches** for dangerous constructs in `src/`: `unserialize`, `serialize`, `eval`, `exec`, weak hashes
   and random functions, TLS options, `http://` literals, `var_dump`/`print_r`, direct reads of the credentials.
4. **CI and supply-chain review**: the workflows, the composite setup action, Dependabot configuration and release
   signing.

Not done: no dynamic testing (fuzzing, penetration test), no review of the third-party development tools beyond
`composer audit` in CI, and the live URSSAF sandbox was not exercised.

## Result

No vulnerability exploitable in the default or the documented configuration was found. The review recorded eight
observations, all low or informational, listed below. The main design properties were confirmed: no third-party runtime
code, secrets never logged nor serialised, personal data of results masked in dumps, strict typed decoding of
responses with fail-closed mapping, a closed result type that keeps a rejected code distinct from a valid certificate,
and pinned, least-privilege CI.

## Findings

Severity scale: Critical, High, Medium, Low, Informational. Status: Open (accepted for a later hardening change),
Accepted (residual risk assumed by design or by the host), Mitigated.

| ID | Severity | Description | Status |
|---|---|---|---|
| SR-01 | Low | The base URI override (`ApiConfiguration`, bundle option `base_uri`) accepts any string: an `http://` URL would send the client secret, the token and the security codes in clear. The default environments are HTTPS and the override is an operator setting, but a guard (refuse non-HTTPS outside a loopback host) is cheap. Redirect handling and TLS verification are those of the PSR-18 client provided by the host; the package neither enables nor disables them. | Open |
| SR-02 | Low | The response body is read in full (`(string) $response->getBody()` in `JsonApiClient` and `OAuth2TokenProvider`) and decoded with a depth limit of 512, without a size cap. A malicious or compromised endpoint could force a large allocation. Mitigated by the client timeout and PHP `memory_limit`; the cap would have to be applied on the stream. | Open |
| SR-03 | Low | `UrssafHttpGateway` checks that a response holds the expected number of results and that each item carries exactly one of a certificate or a fault, but it does not check that the `codeSecurite` of each result equals the code requested at the same position. Only a compromised endpoint (or a proxy that defeats TLS) could exploit this, and such an endpoint could answer "valid" for the right code anyway; the check would still catch a faulty correlation. | Open |
| SR-04 | Informational | The security code is a query parameter of the `GET /codes_securite/verifier` operation. The package never logs the query string (only the path), but the host's HTTP client, reverse proxy or the URSSAF side may record full URLs. `verifyMany()` sends the codes in the JSON body instead. Listed in the assumptions of the assurance case. | Accepted |
| SR-05 | Informational | `SecurityCode` only trims and rejects blank values, because the specification defines no format (see `docs/api-reference.md` section 5). `SecurityCode` also has `__toString()` and is not masked in `var_dump()`, unlike the personal data of the results. A code is not a secret of the package, but it unlocks certificate details for whoever holds it. | Accepted |
| SR-06 | Informational | The access token is stored in clear in the PSR-6 / PSR-16 pool for its lifetime (default pool `cache.app`, often a filesystem directory). Short lived and bound to one client id, but the host must keep the pool private. Listed in the assumptions of the assurance case. | Accepted |
| SR-07 | Informational | `client_secret` handling depends on the host: `%env()%` placeholders are documented, but a literal value in committed configuration is accepted by the bundle and would end in the compiled container. A host-side secret store is assumed. | Accepted |
| SR-08 | Informational | There is no client-side rate limiter (URSSAF publishes no quota values, [ADR 0004](../adr/0004-quotas-and-retry.md)): a public form built on the package can be used to enumerate security codes against the host's quota. This is an availability and abuse concern of the host, not a confidentiality or integrity one. | Accepted |

## Follow-up

SR-01 to SR-03 are candidates for a hardening change, each with its tests (the project gates are 100 % line
coverage, at least 96 % branch coverage and 100 % MSI). They are not blocking: none is exploitable in the default
configuration with a host that follows the assumptions of the assurance case.
