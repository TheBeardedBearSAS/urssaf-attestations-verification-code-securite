# URSSAF "API Attestations Vérification Code Sécurité" for PHP

`thebeardedbear/urssaf-attestations-verification-code-securite` — client, domain model and Symfony integration for the
URSSAF API that checks the validity of a certificate (*attestation de vigilance*, CSG/CRDS, formation
professionnelle, micro-entrepreneur turnover, …) from the security code printed on it.

Namespace: `TheBeardedBear\UrssafAttestationsVerificationCodeSecurite`.

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

With the private Flex endpoint, `composer require` registers and configures the bundle (see [installation with Symfony Flex](docs/symfony.md#installation-with-symfony-flex)); otherwise, register it by hand in `config/bundles.php`:

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
- [docs/reference/](docs/reference/) — official URSSAF documentation.
- Catalogue page: https://portailapi.urssaf.fr/fr/catalogue-api/prd/avcs

## Requirements

PHP 8.2+. Development only needs Docker: every `make` target runs in a container.

## Development

```sh
make install     # build the image, install dependencies
make ci          # full quality gate: cs, rector, phpstan, mago, deptrac, composer checks, guard, coverage, infection
make test        # unit + functional tests
make help        # every target
```

`make ci PHP_VERSION=8.4` runs the gate on another PHP version (PHPStan always runs on 8.2, see
[ADR 0007](docs/adr/0007-static-analysis-on-minimum-php.md)).

`make test-contract` runs the live sandbox tests: set `URSSAF_SANDBOX_CLIENT_ID` and `URSSAF_SANDBOX_CLIENT_SECRET` (required), optionally `URSSAF_SANDBOX_BASE_URI`, `URSSAF_SANDBOX_VALID_SECURITY_CODE`, `URSSAF_SANDBOX_UNKNOWN_SECURITY_CODE` (default `xxx`); without them every contract test is skipped. In CI the manual `contract` job reads the credentials and the valid code from repository secrets, the other two from repository variables.

## Versioning

Semantic Versioning. `SpecVersion::CURRENT` holds the version of the official URSSAF documentation implemented.

## License

Proprietary — see [LICENSE](LICENSE).
