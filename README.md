# URSSAF "API Attestations Vérification Code Sécurité" for PHP

<!-- the-bearded-bear:badges:start -->
[![CI](https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/actions/workflows/ci.yml?query=branch%3Amain) [![Coverage](https://codecov.io/gh/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/branch/main/graph/badge.svg)](https://app.codecov.io/gh/TheBeardedBearSAS/urssaf-attestations-verification-code-securite) [![Mutation score](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2FTheBeardedBearSAS%2Furssaf-attestations-verification-code-securite%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/main) [![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=TheBeardedBearSAS_urssaf-attestations-verification-code-securite&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=TheBeardedBearSAS_urssaf-attestations-verification-code-securite) [![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=TheBeardedBearSAS_urssaf-attestations-verification-code-securite&metric=sqale_rating)](https://sonarcloud.io/summary/overall?id=TheBeardedBearSAS_urssaf-attestations-verification-code-securite) [![Reliability](https://sonarcloud.io/api/project_badges/measure?project=TheBeardedBearSAS_urssaf-attestations-verification-code-securite&metric=reliability_rating)](https://sonarcloud.io/summary/overall?id=TheBeardedBearSAS_urssaf-attestations-verification-code-securite) [![Security](https://sonarcloud.io/api/project_badges/measure?project=TheBeardedBearSAS_urssaf-attestations-verification-code-securite&metric=security_rating)](https://sonarcloud.io/summary/overall?id=TheBeardedBearSAS_urssaf-attestations-verification-code-securite) [![OpenSSF Scorecard](https://api.scorecard.dev/projects/github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite/badge)](https://scorecard.dev/viewer/?uri=github.com/TheBeardedBearSAS/urssaf-attestations-verification-code-securite) [![OpenSSF Best Practices](https://www.bestpractices.dev/projects/16749/badge)](https://www.bestpractices.dev/projects/16749)
[![Packagist version](https://img.shields.io/packagist/v/thebeardedbear/urssaf-attestations-verification-code-securite)](https://packagist.org/packages/thebeardedbear/urssaf-attestations-verification-code-securite) [![PHP version](https://img.shields.io/packagist/dependency-v/thebeardedbear/urssaf-attestations-verification-code-securite/php)](https://packagist.org/packages/thebeardedbear/urssaf-attestations-verification-code-securite) [![Downloads](https://img.shields.io/packagist/dt/thebeardedbear/urssaf-attestations-verification-code-securite)](https://packagist.org/packages/thebeardedbear/urssaf-attestations-verification-code-securite/stats) [![PHPStan level max](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](phpstan.neon.dist) [![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
<!-- the-bearded-bear:badges:end -->

`thebeardedbear/urssaf-attestations-verification-code-securite` — client, domain model and Symfony integration for the
URSSAF API that checks the validity of a certificate (*attestation de vigilance*, CSG/CRDS, formation
professionnelle, micro-entrepreneur turnover, …) from the security code printed on it.

Namespace: `TheBeardedBear\UrssafAttestationsVerificationCodeSecurite`.

Developed and maintained by [The Bearded Bear](https://the-bearded-bear.com).

> [!IMPORTANT]
> **Unofficial package.** This project is an independent open-source implementation of a public API. It is not
> developed, endorsed, certified or supported by URSSAF, and it is not affiliated with URSSAF in any way. "URSSAF" is
> used only to name the API this package talks to. Using this package does not replace the URSSAF habilitation
> process; the URSSAF documentation remains the authority on the API, and you remain responsible for how your
> application uses the verification results.

> Status: implementation complete for the specification v1.2.1; open points are tracked in docs/api-reference.md §5.

## Scope

| Operation | Endpoint |
|---|---|
| Verify one certificate | `GET /codes_securite/verifier?codeSecurite=…` |
| Verify up to 100 certificates | `POST /codes_securite/verifier` |

OAuth2 client credentials, scope `attn.codes_securite`, base URL `https://api.urssaf.fr/attn/verification/v1`.

## Quick start

### Without a framework

```php
use Symfony\Component\HttpClient\Psr18Client;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Port\Credentials;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Application\Query\VerifyCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\RejectedCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\SecurityCode;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Certificate\VerifiedCertificate;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Domain\Shared\TenantId;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Credentials\InMemoryCredentialsProvider;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\ApiConfiguration;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\Environment;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Infrastructure\Urssaf\HttpTransport;
use TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Standalone\UrssafAttestationsVerificationBuilder;

// Requires a PSR-17 implementation: composer require symfony/http-client nyholm/psr7
$client = new Psr18Client();
$urssaf = UrssafAttestationsVerificationBuilder::create(
    new HttpTransport($client, $client, $client),
    new InMemoryCredentialsProvider(['tenant-1' => Credentials::of($clientId, $clientSecret)]),
    ApiConfiguration::forEnvironment(Environment::Production),
)->build();

$result = $urssaf->verifyCertificate()(new VerifyCertificate(
    TenantId::fromString('tenant-1'),
    SecurityCode::fromString($codeTypedByTheUser),
));

if ($result instanceof VerifiedCertificate) {
    // valid certificate: nature, issuing body, issue date, details
} elseif ($result instanceof RejectedCode) {
    // expired, unknown or incomplete code: an answer of URSSAF, not an exception
}
```

See [docs/standalone.md](docs/standalone.md).

### Symfony

With the Symfony Flex contrib recipe, `composer require` registers and configures the bundle (see [installation with Symfony Flex](docs/symfony.md#installation-with-symfony-flex)); otherwise, register it by hand in `config/bundles.php`:

```php
return [
    // ...
    TheBeardedBear\UrssafAttestationsVerificationCodeSecurite\Bridge\Symfony\UrssafAttestationsVerificationCodeSecuriteBundle::class => ['all' => true],
];
```

Minimal configuration in `config/packages/urssaf_attestations_verification_code_securite.yaml`:

```yaml
urssaf_attestations_verification_code_securite:
    environment: '%env(URSSAF_ENV)%'            # sandbox | production
    credentials:
        tenant-1:
            client_id: '%env(URSSAF_CLIENT_ID)%'
            client_secret: '%env(URSSAF_CLIENT_SECRET)%'
```

Inject `Application\Query\VerifyCertificateHandler` in your services and call it with a `VerifyCertificate` query, or verify a
code from the shell:

```sh
bin/console urssaf:certificate:verify <code>
```

See [docs/symfony.md](docs/symfony.md).

## Architecture

The package follows a hexagonal (ports and adapters) architecture: the application layer runs the use cases through
ports, and the infrastructure implements those ports against the URSSAF API. Deptrac enforces the dependency rules
between the layers, and the runtime depends on PSR interfaces only. The reference architecture is described in
[docs/adr/0000-reference-architecture.md](docs/adr/0000-reference-architecture.md).

- `Domain`: security codes, certificates, the verification result, domain exceptions.
- `Application`: queries and their handlers, ports (gateway, credentials, token store).
- `Infrastructure`: URSSAF HTTP gateway, OAuth2, wire mappers, token stores, credentials providers.
- `Standalone`: framework-free builder and entry point.
- `Testing`: in-memory simulated gateway for the tests of host applications.
- `Bridge\Symfony`: Symfony bundle, configuration and console commands.

## Documentation

- [docs/api-reference.md](docs/api-reference.md) — technical reference (single source of truth for the package).
- [docs/roadmap.md](docs/roadmap.md) — planned architecture and order of work.
- [docs/adr/](docs/adr/) — architecture decision records (0000: decisions inherited from the reference package).
- [docs/standalone.md](docs/standalone.md) — usage without a framework.
- [docs/testing.md](docs/testing.md) — testing an application that uses the package.
- [docs/symfony.md](docs/symfony.md) — Symfony bundle.
- [docs/openapi/](docs/openapi/) — official OpenAPI contract(s) from the URSSAF API portal.
- `docs/reference/` — official URSSAF documentation: not versioned (URSSAF documents), download it from the links in [docs/api-reference.md](docs/api-reference.md) into this local, git-ignored folder.
- Catalogue page: https://portailapi.urssaf.fr/fr/catalogue-api/prd/avcs

## Requirements

PHP 8.2+. Development only needs Docker: every `make` target runs in a container.

## Development

```sh
make install     # build the image, install dependencies
make ci          # full quality gate: cs, rector, phpstan, mago, deptrac, composer checks, hadolint, guard, coverage, infection
make test        # unit + functional tests
make bench       # benchmarks (PHPBench): security code validation, wire encoding and decoding
make help        # every target
```

`make ci PHP_VERSION=8.4` runs the gate on another PHP version (PHPStan always runs on 8.2, see
[ADR 0007](docs/adr/0007-static-analysis-on-minimum-php.md)).

Published proofs (public repository, `main`): the coverage report on Codecov, the full mutation report on the Stryker
Dashboard, the SonarQube Cloud quality gate and the OpenSSF Scorecard (`.github/workflows/scorecard.yml`), linked from
the badges at the top of this file. The benchmarks (`tests/Benchmark`, PHPBench in `tools/phpbench`, out of the package
dependencies) run on every pull request against the base commit, and the job fails on a regression of more than 20 %.

`make test-contract` runs the live sandbox tests: set `URSSAF_SANDBOX_CLIENT_ID` and `URSSAF_SANDBOX_CLIENT_SECRET` (required), optionally `URSSAF_SANDBOX_BASE_URI`, `URSSAF_SANDBOX_VALID_SECURITY_CODE`, `URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE` (default `xxx`); without them every contract test is skipped. In CI the manual `contract` job reads the credentials and the valid code from repository secrets, the other two from repository variables.

## Versioning

Semantic Versioning. `SpecVersion::CURRENT` holds the version of the official URSSAF documentation implemented.

<!-- the-bearded-bear:start -->
## Other URSSAF packages

The same architecture, quality gate and conventions, one package per URSSAF API (overview: [the-bearded-bear.com/open-source/urssaf](https://the-bearded-bear.com/open-source/urssaf)):

| API | Package | Status |
|---|---|---|
| Tiers de prestations (immediate tax-credit advance) | [`thebeardedbear/urssaf-tiers-prestations`](https://github.com/TheBeardedBearSAS/urssaf-tiers-prestations) | Released (Packagist) |
| DPAE (pre-employment declaration) | [`thebeardedbear/urssaf-dpae`](https://github.com/TheBeardedBearSAS/urssaf-dpae) | Coming soon |
| Mobilité internationale (posting of workers abroad) | [`thebeardedbear/urssaf-mobilite-internationale`](https://github.com/TheBeardedBearSAS/urssaf-mobilite-internationale) | Coming soon |
| Plateforme numérique (withholding of contributions by digital platforms) | [`thebeardedbear/urssaf-plateforme-numerique`](https://github.com/TheBeardedBearSAS/urssaf-plateforme-numerique) | Coming soon |
| Tierce Déclaration auto-entrepreneur | [`thebeardedbear/urssaf-tierce-declaration-auto-entrepreneur`](https://github.com/TheBeardedBearSAS/urssaf-tierce-declaration-auto-entrepreneur) | Coming soon |
| Tierce Déclaration CESU | [`thebeardedbear/urssaf-tierce-declaration-cesu`](https://github.com/TheBeardedBearSAS/urssaf-tierce-declaration-cesu) | Released (Packagist) |
| Tierce Déclaration PAJE | [`thebeardedbear/urssaf-tierce-declaration-paje`](https://github.com/TheBeardedBearSAS/urssaf-tierce-declaration-paje) | Coming soon |

## Professional support

This package is developed and maintained by [The Bearded Bear](https://the-bearded-bear.com).
If you need help with an URSSAF integration, The Bearded Bear can help with:

- the URSSAF habilitation and homologation process (subscription, sandbox tests, move to production);
- integrating the package into your application (Symfony, Doctrine or framework-free);
- support with a guaranteed response time, including the follow-up of URSSAF API changes;
- custom developments on top of the package.

Project page and integration offer: [the-bearded-bear.com/open-source/urssaf-attestations-verification-code-securite](https://the-bearded-bear.com/open-source/urssaf-attestations-verification-code-securite).
<!-- the-bearded-bear:end -->

## Contributing and security

Contributions are welcome: see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately as described in
[SECURITY.md](SECURITY.md), never in a public issue.

## License

MIT. See [LICENSE](LICENSE). The software is provided "as is", without warranty of any kind.

The API specification summarised in [docs/api-reference.md](docs/api-reference.md) belongs to URSSAF; see the sources
listed there.
